<?php

namespace App\Http\Controllers;

use App\Models\GerbangLog;
use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use App\Support\RfidAccess;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RfidTapController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = RfidAccess::extractDeviceToken($request);
        $isDeviceKey = RfidAccess::isValidDeviceKey($token);

        // Setiap interaksi sah dari ESP32 langsung memperbarui status reader online
        if ($isDeviceKey) {
            $deviceId = $request->input('device_id');
            RfidAccess::touchDeviceHeartbeat(
                is_string($deviceId) ? $deviceId : null,
                $request->ip(),
            );
        }

        // Endpoint reset kartu darurat/testing untuk device key
        if ($isDeviceKey && $request->input('action') === 'reset_card') {
            if ($request->input('uid')) {
                $uidHash = RfidCard::hashUid($request->input('uid'));
                RfidCard::where('uid_hash', $uidHash)->delete();

                return response()->json(['status' => 'success', 'message' => 'Kartu dengan UID tersebut berhasil direset.']);
            }
            if ($request->input('user_id')) {
                RfidAccess::revokeCard((int) $request->input('user_id'));

                return response()->json(['status' => 'success', 'message' => 'Kartu warga berhasil direset.']);
            }
        }

        $requestedMode = strtolower((string) ($request->route('mode') ?? $request->input('mode') ?? ''));
        $forceGateMode = in_array($requestedMode, ['gate', 'gerbang'], true);
        $rawDeviceId = $request->input('device_id');
        $deviceId = is_string($rawDeviceId) && trim($rawDeviceId) !== ''
            ? substr(trim($rawDeviceId), 0, 64)
            : null;

        if (! $isDeviceKey) {
            abort_unless(is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token), 401);
            $hash = hash('sha256', $token);
            $initial = RfidSession::where('token_hash', $hash)->first();
            abort_unless($initial, 401);
        } else {
            // Auto-resolve session menggunakan Device Key permanen (bebas token sesi manual)
            // 1. Prioritaskan sesi enrollment aktif yang sedang menunggu tap kartu warga
            $initial = RfidSession::whereNotNull('enrollment_user_id')
                ->where('enrollment_expires_at', '>', now())
                ->latest('updated_at')
                ->first();

            // 1b. Jika endpoint/payload meminta mode gerbang, pakai sesi gerbang aktif atau master gerbang
            if (! $initial && $forceGateMode) {
                $initial = RfidSession::where('scope_type', 'gerbang')
                    ->where('expires_at', '>', now())
                    ->latest('updated_at')
                    ->first()
                    ?? $this->resolveOrCreateMasterSession('gerbang:master:device', 'gerbang', (string) $token, [
                        'gate_mode' => 'auto',
                    ]);
            }

            // 2. Jika tidak ada enrollment, cari sesi kegiatan yang sedang berlangsung
            if (! $initial && ! $forceGateMode) {
                $initial = RfidSession::where('scope_type', 'kegiatan')
                    ->where('expires_at', '>', now())
                    ->whereHas('kegiatan', fn ($q) => $q
                        ->whereNotIn('status', ['selesai', 'dibatalkan'])
                        ->where('tanggal_mulai', '<=', now())
                        ->where(fn ($sq) => $sq->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>', now())))
                    ->latest('updated_at')
                    ->first();
            }

            // 3. Jika tidak ada kegiatan, cari sesi aktif apapun
            if (! $initial) {
                $initial = RfidSession::where('expires_at', '>', now())
                    ->latest('updated_at')
                    ->first();
            }

            // 4. Fallback: sesi master gerbang aktif 24/7 agar e-KTP terverifikasi langsung bisa buka gerbang
            if (! $initial) {
                $initial = $this->resolveOrCreateMasterSession('gerbang:master:device', 'gerbang', (string) $token, [
                    'gate_mode' => 'auto',
                ]);
            }

            $hash = $initial->token_hash;
        }

        // Simpan enrollment_user_id SEBELUM masuk transaksi (untuk notifikasi di luar transaksi)
        $enrollmentUserId = $initial->enrollment_user_id;
        $resolvedRtId = $initial->rt_id;
        $rawUid = is_string($request->input('uid')) ? strtoupper($request->input('uid')) : '';
        $deniedMeta = [
            'rw_id' => $initial->rw_id ? (int) $initial->rw_id : null,
            'rt_id' => $initial->rt_id ? (int) $initial->rt_id : null,
            'user_id' => null,
            'rfid_card_id' => null,
        ];

        $publishScanResult = function (
            string $status,
            string $msg,
            ?string $type = null,
            ?string $userName = null,
            ?string $feedback = null,
            ?string $direction = null,
            bool $openGate = false,
        ) use (&$rawUid, &$resolvedRtId, $initial, $enrollmentUserId) {
            $scanPayload = [
                'id' => (string) Str::uuid(),
                'uid' => $rawUid !== '' ? $rawUid : 'UNKNOWN',
                'uid_masked' => RfidAccess::maskUid($rawUid),
                'status' => $status,
                'type' => $type ?? $status,
                'feedback' => $feedback ?? ($status === 'success' ? 'ok' : 'error'),
                'user_name' => $userName,
                'scope_type' => $initial?->scope_type,
                'direction' => $direction,
                'open_gate' => $openGate,
                'message' => $msg,
                'at' => now()->toIso8601String(),
                'timestamp' => microtime(true),
            ];

            // 1. Tulis ke cache global agar status umum / superadmin langsung terupdate
            Cache::put('rfid_last_scan_global', $scanPayload, now()->addMinutes(2));

            // 2. Tulis ke cache sesi spesifik untuk operator kegiatan / koperasi / KTP RT / gerbang
            if ($initial?->id) {
                Cache::put("rfid_last_scan_session:{$initial->id}", $scanPayload, now()->addMinutes(2));
                Cache::put("rfid_session_notice:{$initial->id}", $msg, now()->addMinutes(2));
            }

            // 3. Tulis ke cache RT spesifik (hanya untuk konteks KTP warga agar tidak tercampur modul kegiatan)
            if ($resolvedRtId && $initial?->scope_type === 'warga') {
                Cache::put("rfid_last_scan_rt:{$resolvedRtId}", $scanPayload, now()->addMinutes(2));
            }

            // 4. Tulis ke cache user yang sedang membuka sesi tap mandiri
            if ($enrollmentUserId) {
                Cache::put("rfid_scan_result:{$enrollmentUserId}", $scanPayload, now()->addMinutes(2));
                Cache::put("rfid_enrollment_notice:{$enrollmentUserId}", $msg, now()->addMinutes(2));
            }
        };

        $recordDeniedGateAttempt = function (string $msg) use ($initial, &$rawUid, $deviceId, &$deniedMeta, &$resolvedRtId) {
            if ($initial?->scope_type !== 'gerbang') {
                return;
            }

            $resolvedRtId ??= $deniedMeta['rt_id'];

            try {
                GerbangLog::create([
                    'rw_id' => $deniedMeta['rw_id'],
                    'rt_id' => $deniedMeta['rt_id'],
                    'user_id' => $deniedMeta['user_id'],
                    'rfid_card_id' => $deniedMeta['rfid_card_id'],
                    'uid_masked' => RfidAccess::maskUid($rawUid),
                    'arah' => 'ditolak',
                    'status' => 'ditolak',
                    'device_id' => $deviceId ?: 'esp32-default',
                    'keterangan' => Str::limit($msg, 255),
                    'tapped_at' => now(),
                ]);
            } catch (\Throwable) {
                // Keep validation/HTTP response intact if logging fails.
            }
        };

        try {
            $data = $request->validate(['uid' => ['required', 'string', 'regex:/\A(?:[a-fA-F0-9]{8}|[a-fA-F0-9]{14}|[a-fA-F0-9]{20})\z/']]);
            $rawUid = strtoupper($data['uid']);

            if ($initial->scope_type === 'gerbang') {
                $response = $this->handleGerbangTap($initial, $hash, $rawUid, $isDeviceKey, $resolvedRtId, $deviceId, $deniedMeta);
            } elseif ($initial->scope_type === 'koperasi') {
                $response = $this->handleKoperasiTap($initial, $hash, $rawUid, $isDeviceKey, $resolvedRtId);
            } elseif ($initial->scope_type === 'warga') {
                $response = $this->handleWargaKtpTap($initial, $hash, $rawUid, $isDeviceKey, $resolvedRtId);
            } else {
                $response = $this->handleKegiatanTap($initial, $hash, $rawUid, $isDeviceKey, $resolvedRtId);
            }

            // SUKSES: Publikasikan hasil scan secara komprehensif ke cache DI LUAR DB::transaction
            $responseData = $response->getData();
            $msg = $responseData->message ?? 'Operasi berhasil.';
            $publishScanResult(
                'success',
                $msg,
                $responseData->status ?? 'success',
                $responseData->user_name ?? null,
                $responseData->feedback ?? 'ok',
                $responseData->direction ?? null,
                (bool) ($responseData->open_gate ?? false),
            );

            return $response;
        } catch (ValidationException $e) {
            // ERROR VALIDASI: Publikasikan ke cache DI LUAR DB::transaction
            $msg = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            $recordDeniedGateAttempt($msg);
            $publishScanResult('error', $msg, 'validation_error', null, 'error');

            throw $e;
        } catch (UniqueConstraintViolationException $e) {
            $msg = 'Kartu atau warga sudah terdaftar. Silakan periksa kembali.';
            $recordDeniedGateAttempt($msg);
            $publishScanResult('error', $msg, 'conflict_error', null, 'error');

            throw ValidationException::withMessages(['uid' => $msg]);
        } catch (HttpExceptionInterface $e) {
            $msg = $e->getMessage();
            $recordDeniedGateAttempt($msg);
            $publishScanResult('error', $msg, 'http_error', null, 'error');

            throw $e;
        } catch (\Throwable $e) {
            $msg = $e->getMessage() ?: 'Terjadi kesalahan sistem saat memproses tap kartu.';
            $recordDeniedGateAttempt($msg);
            $publishScanResult('error', $msg, 'system_error', null, 'error');

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveOrCreateMasterSession(string $scopeKey, string $scopeType, string $rawToken, array $attributes = []): RfidSession
    {
        $adminId = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'superadmin', 'admin', 'ketua_rt']))->value('id')
            ?? User::value('id')
            ?? 1;

        $session = RfidSession::firstOrCreate(
            ['scope_key' => $scopeKey],
            [
                ...$attributes,
                'scope_type' => $scopeType,
                'created_by' => $adminId,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addYears(10),
            ]
        );

        if ($session->expires_at->isPast()) {
            $session->update(['expires_at' => now()->addYears(10)]);
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function tapSuccessResponse(string $status, string $message, string $uid, string $feedback, string $userName, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $status,
            'feedback' => $feedback,
            'uid' => strtoupper($uid),
            'user_name' => $userName,
            'message' => $message,
            'open_gate' => false,
            'servo' => false,
            ...$extra,
        ]);
    }

    /**
     * @param  array{rw_id: int|null, rt_id: int|null, user_id: int|null, rfid_card_id: int|null}  $deniedMeta
     */
    private function handleGerbangTap(
        RfidSession $initial,
        string $hash,
        string $uid,
        bool $isDeviceKey = false,
        ?int &$resolvedRtId = null,
        ?string $deviceId = null,
        array &$deniedMeta = [],
    ): JsonResponse {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey, &$resolvedRtId, $deviceId, &$deniedMeta) {
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless($session->scope_type === 'gerbang' && ($isDeviceKey || hash_equals($session->token_hash, $hash)) && $session->expires_at->isFuture(), 401, 'Sesi gerbang tidak aktif atau sudah kedaluwarsa.');
            $actor = User::find($session->created_by);
            abort_unless(($actor && RfidAccess::canManageGerbang($actor)) || $isDeviceKey, 403);

            $uidHash = RfidCard::hashUid($uid);
            $card = RfidCard::where('uid_hash', $uidHash)->lockForUpdate()->first();
            abort_unless($card, 422, 'Kartu tidak terdaftar. Daftarkan e-KTP warga terlebih dahulu.');

            $deniedMeta['rfid_card_id'] = $card->id;
            $user = User::lockForUpdate()->find($card->user_id);
            abort_unless($user && $user->isAktif(), 422, 'Akun pemilik kartu tidak aktif atau tidak ditemukan.');

            $deniedMeta['user_id'] = $user->id;
            $deniedMeta['rt_id'] ??= $user->rt_id ? (int) $user->rt_id : null;
            $deniedMeta['rw_id'] ??= $user->assignedRwId();

            abort_unless($card->ktp_verified_at !== null, 422, 'Kartu e-KTP warga belum terverifikasi di RT/sistem.');

            if ($session->rt_id || $session->rw_id) {
                $inScope = RfidAccess::gerbangParticipants($session)->whereKey($user->id)->exists();
                abort_unless($inScope, 403, 'Kartu e-KTP warga berada di luar wilayah RT/RW gerbang ini.');
            }

            $mode = in_array($session->gate_mode, ['masuk', 'keluar'], true) ? $session->gate_mode : 'auto';
            if ($mode === 'auto') {
                $lastAllowed = GerbangLog::where('user_id', $user->id)
                    ->where('status', 'diizinkan')
                    ->latest('tapped_at')
                    ->latest('id')
                    ->first();
                $arah = ($lastAllowed && $lastAllowed->arah === 'masuk') ? 'keluar' : 'masuk';
            } else {
                $arah = $mode;
            }

            $logRtId = $user->rt_id ? (int) $user->rt_id : ($session->rt_id ? (int) $session->rt_id : null);
            $logRwId = $user->assignedRwId() ? (int) $user->assignedRwId() : ($session->rw_id ? (int) $session->rw_id : null);
            $msg = $arah === 'masuk'
                ? "Gerbang MASUK dibuka untuk {$user->name}."
                : "Gerbang KELUAR dibuka untuk {$user->name}.";

            GerbangLog::create([
                'rw_id' => $logRwId,
                'rt_id' => $logRtId,
                'user_id' => $user->id,
                'rfid_card_id' => $card->id,
                'uid_masked' => RfidAccess::maskUid($uid),
                'arah' => $arah,
                'status' => 'diizinkan',
                'device_id' => $deviceId ?: 'esp32-default',
                'keterangan' => $msg,
                'tapped_at' => now(),
            ]);

            $session->update(['last_seen_at' => now()]);
            $resolvedRtId = $resolvedRtId ?? $logRtId;

            return $this->tapSuccessResponse(
                'gate_opened',
                $msg,
                $uid,
                'ok',
                $user->name,
                [
                    'open_gate' => true,
                    'servo' => true,
                    'direction' => $arah,
                    'buzzer' => $arah === 'masuk' ? 'gate_in' : 'gate_out',
                ],
            );
        }, 3);
    }

    private function handleKegiatanTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false, ?int &$resolvedRtId = null)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey, &$resolvedRtId) {
            // Same lock order as open/close/enrollment; recheck token after acquiring locks.
            $kegiatan = Kegiatan::whereKey($initial->kegiatan_id)->lockForUpdate()->firstOrFail();
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless(($isDeviceKey || hash_equals($session->token_hash, $hash)) && $session->expires_at->isFuture(), 401);
            $actor = User::find($session->created_by);
            abort_unless(($actor && RfidAccess::canManage($actor, $kegiatan)) || $isDeviceKey, 403);
            abort_if(in_array($kegiatan->effective_status, ['selesai', 'dibatalkan'], true), 409, 'Kegiatan sudah ditutup.');
            $uidHash = RfidCard::hashUid($uid);

            if ($session->enrollment_user_id) {
                if (! $session->enrollment_expires_at?->isFuture()) {
                    $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null]);
                    throw ValidationException::withMessages(['uid' => 'Pendaftaran kedaluwarsa. Pengurus harus membatalkan atau memulai ulang.']);
                }
                $user = RfidAccess::participants($kegiatan)->whereKey($session->enrollment_user_id)->lockForUpdate()->firstOrFail();
                $existingCard = RfidCard::where('uid_hash', $uidHash)->with('user')->first();
                if ($existingCard && $existingCard->user_id !== $user->id) {
                    $ownerName = $existingCard->user?->name ?? 'warga lain';
                    $msg = "Kartu RFID fisik ini sudah terdaftar di akun warga lain: {$ownerName}. Harap gunakan kartu milik Anda sendiri.";
                    Cache::put("rfid_enrollment_notice:{$user->id}", $msg, now()->addMinutes(2));
                    Cache::put("rfid_session_notice:{$session->id}", $msg, now()->addMinutes(2));
                    throw ValidationException::withMessages(['uid' => $msg]);
                }
                if (RfidCard::where('user_id', $user->id)->orWhere('uid_hash', $uidHash)->exists()) {
                    throw ValidationException::withMessages(['uid' => 'Kartu atau warga sudah terdaftar. Cabut kartu lama terlebih dahulu.']);
                }
                RfidCard::create(['user_id' => $user->id, 'uid_hash' => $uidHash]);
                $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null, 'last_seen_at' => now()]);
                $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

                return $this->tapSuccessResponse('registered', 'Kartu terdaftar. Tap lagi untuk absensi.', $uid, 'ok', $user->name);
            }

            // Registration may happen before an event; physical attendance may not.
            abort_unless($kegiatan->effective_status === 'berlangsung', 409, 'Kegiatan belum dimulai.');
            $card = RfidCard::where('uid_hash', $uidHash)->first();
            abort_unless($card, 422, 'Kartu belum terdaftar.');
            $user = RfidAccess::participants($kegiatan)->whereKey($card->user_id)->lockForUpdate()->firstOrFail();
            // Recheck after locking the user to serialize with card revocation.
            abort_unless(RfidCard::whereKey($card->id)->where('uid_hash', $uidHash)->exists(), 422, 'Kartu telah dicabut.');
            $hadir = KegiatanHadir::firstOrCreate(['kegiatan_id' => $kegiatan->id, 'user_id' => $user->id]);
            $duplicate = $hadir->rfid_hadir_at !== null;
            if (! $duplicate) {
                $hadir->update(['rfid_hadir_at' => now()]);
            }
            $session->update(['last_seen_at' => now()]);
            $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

            return $this->tapSuccessResponse(
                $duplicate ? 'already_present' : 'present',
                $duplicate ? 'Sudah tercatat hadir.' : 'Kehadiran berhasil dicatat.',
                $uid,
                $duplicate ? 'duplicate' : 'ok',
                $user->name,
            );
        }, 3);
    }

    private function handleKoperasiTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false, ?int &$resolvedRtId = null)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey, &$resolvedRtId) {
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless($session->scope_type === 'koperasi' && ($isDeviceKey || hash_equals($session->token_hash, $hash)) && $session->expires_at->isFuture(), 401);
            $actor = User::find($session->created_by);
            abort_unless(($actor && RfidAccess::canManageKoperasi($actor) && RfidAccess::matchesKoperasiScope($actor, $session)) || $isDeviceKey, 403);
            $uidHash = RfidCard::hashUid($uid);

            if ($session->enrollment_user_id) {
                if (! $session->enrollment_expires_at?->isFuture()) {
                    $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null]);
                    throw ValidationException::withMessages(['uid' => 'Pendaftaran kedaluwarsa. Pengurus harus membatalkan atau memulai ulang.']);
                }
                $user = RfidAccess::koperasiParticipants($session)->whereKey($session->enrollment_user_id)->lockForUpdate()->firstOrFail();
                $existingCard = RfidCard::where('uid_hash', $uidHash)->with('user')->first();
                if ($existingCard && $existingCard->user_id !== $user->id) {
                    $ownerName = $existingCard->user?->name ?? 'warga lain';
                    $msg = "Kartu RFID fisik ini sudah terdaftar di akun warga lain: {$ownerName}. Harap gunakan kartu milik Anda sendiri.";
                    Cache::put("rfid_enrollment_notice:{$user->id}", $msg, now()->addMinutes(2));
                    Cache::put("rfid_session_notice:{$session->id}", $msg, now()->addMinutes(2));
                    throw ValidationException::withMessages(['uid' => $msg]);
                }
                if (RfidCard::where('user_id', $user->id)->orWhere('uid_hash', $uidHash)->exists()) {
                    throw ValidationException::withMessages(['uid' => 'Kartu atau warga sudah terdaftar. Cabut kartu lama terlebih dahulu.']);
                }
                RfidCard::create([
                    'user_id' => $user->id,
                    'uid_hash' => $uidHash,
                    'koperasi_verified_at' => now(),
                ]);
                $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null, 'last_seen_at' => now()]);
                $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

                return $this->tapSuccessResponse(
                    'koperasi_registered',
                    'Kartu terdaftar & terverifikasi untuk pendaftaran koperasi.',
                    $uid,
                    'ok',
                    $user->name,
                );
            }

            $card = RfidCard::where('uid_hash', $uidHash)->first();
            abort_unless($card, 422, 'Kartu belum terdaftar.');
            $user = RfidAccess::koperasiParticipants($session)->whereKey($card->user_id)->lockForUpdate()->firstOrFail();
            $lockedCard = RfidCard::whereKey($card->id)->where('uid_hash', $uidHash)->lockForUpdate()->first();
            abort_unless($lockedCard, 422, 'Kartu telah dicabut.');

            $alreadyVerified = $lockedCard->koperasi_verified_at !== null;
            if (! $alreadyVerified) {
                $lockedCard->update(['koperasi_verified_at' => now()]);
            }
            $session->update(['last_seen_at' => now()]);
            $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

            return $this->tapSuccessResponse(
                $alreadyVerified ? 'koperasi_already_verified' : 'koperasi_verified',
                $alreadyVerified
                    ? 'Kartu sudah terverifikasi untuk daftar koperasi.'
                    : 'Tap kartu berhasil. Warga dapat melanjutkan pendaftaran koperasi.',
                $uid,
                $alreadyVerified ? 'duplicate' : 'ok',
                $user->name,
            );
        }, 3);
    }

    private function handleWargaKtpTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false, ?int &$resolvedRtId = null)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey, &$resolvedRtId) {
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless($session->scope_type === 'warga' && ($isDeviceKey || hash_equals($session->token_hash, $hash)) && $session->expires_at->isFuture(), 401);
            $actor = User::find($session->created_by);

            $hasPermission = $actor && RfidAccess::canManageWargaKtp($actor) && RfidAccess::matchesWargaKtpScope($actor, $session);
            $isSelfEnrollment = $session->enrollment_user_id && $session->enrollment_expires_at?->isFuture();
            abort_unless($hasPermission || $isSelfEnrollment || $isDeviceKey, 403);

            $uidHash = RfidCard::hashUid($uid);

            if ($session->enrollment_user_id) {
                if (! $session->enrollment_expires_at?->isFuture()) {
                    $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null]);
                    throw ValidationException::withMessages(['uid' => 'Pendaftaran KTP kedaluwarsa. Silakan mulai ulang sesi tap.']);
                }
                $user = $isDeviceKey
                    ? User::lockForUpdate()->findOrFail($session->enrollment_user_id)
                    : RfidAccess::wargaKtpParticipants($session)->whereKey($session->enrollment_user_id)->lockForUpdate()->firstOrFail();

                $existingCard = RfidCard::where('uid_hash', $uidHash)->with('user')->first();
                if ($existingCard && $existingCard->user_id !== $user->id) {
                    $ownerName = $existingCard->user?->name ?? 'warga lain';
                    $msg = "Kartu e-KTP fisik ini sudah terdaftar di akun warga lain: {$ownerName}. Silakan gunakan kartu e-KTP milik Anda sendiri.";
                    Cache::put("rfid_enrollment_notice:{$user->id}", $msg, now()->addMinutes(2));
                    Cache::put("rfid_session_notice:{$session->id}", $msg, now()->addMinutes(2));
                    throw ValidationException::withMessages(['uid' => $msg]);
                }

                if (RfidCard::where('user_id', $user->id)->orWhere('uid_hash', $uidHash)->exists()) {
                    throw ValidationException::withMessages(['uid' => 'KTP atau warga sudah terdaftar. Cabut kartu lama terlebih dahulu.']);
                }

                $verifierId = ($actor && RfidAccess::canManageWargaKtp($actor))
                    ? $actor->id
                    : (User::where('rt_id', $user->rt_id)->whereHas('role', fn ($q) => $q->whereIn('name', ['ketua_rt', 'rt']))->value('id')
                        ?? User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'superadmin', 'admin']))->value('id')
                        ?? $user->id);

                RfidCard::create([
                    'user_id' => $user->id,
                    'uid_hash' => $uidHash,
                    'ktp_verified_at' => now(),
                    'verified_by' => $verifierId,
                    'verified_rt_id' => $user->rt_id,
                    'koperasi_verified_at' => now(),
                ]);
                $session->update(['enrollment_user_id' => null, 'enrollment_expires_at' => null, 'last_seen_at' => now()]);
                $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

                return $this->tapSuccessResponse(
                    'ktp_registered',
                    'KTP warga berhasil didaftarkan dan tercatat di RT serta sistem.',
                    $uid,
                    'ok',
                    $user->name,
                );
            }

            $card = RfidCard::where('uid_hash', $uidHash)->first();
            abort_unless($card, 422, 'KTP belum terdaftar. Petugas harus memilih warga dan klik Aktifkan Tap KTP terlebih dahulu.');
            $user = $isDeviceKey
                ? User::lockForUpdate()->findOrFail($card->user_id)
                : RfidAccess::wargaKtpParticipants($session)->whereKey($card->user_id)->lockForUpdate()->firstOrFail();
            $lockedCard = RfidCard::whereKey($card->id)->where('uid_hash', $uidHash)->lockForUpdate()->first();
            abort_unless($lockedCard, 422, 'Kartu telah dicabut.');

            $alreadyVerified = $lockedCard->ktp_verified_at !== null;
            if (! $alreadyVerified) {
                $lockedCard->update([
                    'ktp_verified_at' => now(),
                    'verified_by' => $actor?->id ?? $user->id,
                    'verified_rt_id' => $user->rt_id,
                    'koperasi_verified_at' => $lockedCard->koperasi_verified_at ?? now(),
                ]);
            }
            $session->update(['last_seen_at' => now()]);
            $resolvedRtId = $resolvedRtId ?? ($user->rt_id ? (int) $user->rt_id : null);

            return $this->tapSuccessResponse(
                $alreadyVerified ? 'ktp_already_verified' : 'ktp_verified',
                $alreadyVerified
                    ? 'KTP warga sudah terdaftar di RT dan sistem.'
                    : 'Verifikasi KTP berhasil. KTP warga kini terdaftar di RT dan sistem.',
                $uid,
                $alreadyVerified ? 'duplicate' : 'ok',
                $user->name,
            );
        }, 3);
    }
}
