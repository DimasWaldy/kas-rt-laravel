<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use App\Support\RfidAccess;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfidTapController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = $request->bearerToken();
        $deviceKey = config('services.rfid.device_key', env('RFID_DEVICE_KEY', 'smartrw-rfid-device'));
        $isDeviceKey = is_string($token) && hash_equals($deviceKey, $token);

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

            // 2. Jika tidak ada enrollment, cari sesi kegiatan yang sedang berlangsung
            if (! $initial) {
                $initial = RfidSession::where('scope_type', 'kegiatan')
                    ->where('expires_at', '>', now())
                    ->whereHas('kegiatan', fn ($q) => $q->where('status', 'berlangsung'))
                    ->latest('updated_at')
                    ->first();
            }

            // 3. Jika tidak ada kegiatan, cari sesi aktif apapun
            if (! $initial) {
                $initial = RfidSession::where('expires_at', '>', now())
                    ->latest('updated_at')
                    ->first();
            }

            // 4. Fallback: buat/ambil sesi master warga agar alat selalu siap pakai
            if (! $initial) {
                $admin = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'superadmin', 'admin', 'ketua_rt']))->first()
                    ?? User::first();

                $initial = RfidSession::firstOrCreate(
                    ['scope_key' => 'warga:master:device'],
                    [
                        'scope_type' => 'warga',
                        'created_by' => $admin?->id ?? 1,
                        'token_hash' => hash('sha256', $deviceKey),
                        'expires_at' => now()->addYears(10),
                    ]
                );

                if ($initial->expires_at->isPast()) {
                    $initial->update(['expires_at' => now()->addYears(10)]);
                }
            }

            $hash = $initial->token_hash;
        }

        $data = $request->validate(['uid' => ['required', 'string', 'regex:/\A(?:[a-fA-F0-9]{8}|[a-fA-F0-9]{14}|[a-fA-F0-9]{20})\z/']]);

        try {
            if ($initial->scope_type === 'koperasi') {
                return $this->handleKoperasiTap($initial, $hash, $data['uid'], $isDeviceKey);
            }

            if ($initial->scope_type === 'warga') {
                return $this->handleWargaKtpTap($initial, $hash, $data['uid'], $isDeviceKey);
            }

            return $this->handleKegiatanTap($initial, $hash, $data['uid'], $isDeviceKey);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['uid' => 'Kartu atau warga sudah terdaftar. Silakan periksa kembali.']);
        }
    }

    private function handleKegiatanTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey) {
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

                return response()->json(['status' => 'registered', 'message' => 'Kartu terdaftar. Tap lagi untuk absensi.']);
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

            return response()->json(['status' => $duplicate ? 'already_present' : 'present', 'message' => $duplicate ? 'Sudah tercatat hadir.' : 'Kehadiran berhasil dicatat.']);
        }, 3);
    }

    private function handleKoperasiTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey) {
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

                return response()->json([
                    'status' => 'koperasi_registered',
                    'message' => 'Kartu terdaftar & terverifikasi untuk pendaftaran koperasi.',
                ]);
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

            return response()->json([
                'status' => $alreadyVerified ? 'koperasi_already_verified' : 'koperasi_verified',
                'message' => $alreadyVerified
                    ? 'Kartu sudah terverifikasi untuk daftar koperasi.'
                    : 'Tap kartu berhasil. Warga dapat melanjutkan pendaftaran koperasi.',
            ]);
        }, 3);
    }

    private function handleWargaKtpTap(RfidSession $initial, string $hash, string $uid, bool $isDeviceKey = false)
    {
        return DB::transaction(function () use ($initial, $hash, $uid, $isDeviceKey) {
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

                return response()->json([
                    'status' => 'ktp_registered',
                    'message' => 'KTP warga berhasil didaftarkan dan tercatat di RT serta sistem.',
                ]);
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

            return response()->json([
                'status' => $alreadyVerified ? 'ktp_already_verified' : 'ktp_verified',
                'message' => $alreadyVerified
                    ? 'KTP warga sudah terdaftar di RT dan sistem.'
                    : 'Verifikasi KTP berhasil. KTP warga kini terdaftar di RT dan sistem.',
            ]);
        }, 3);
    }
}
