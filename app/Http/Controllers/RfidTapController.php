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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfidTapController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token), 401);
        $hash = hash('sha256', $token);
        $initial = RfidSession::where('token_hash', $hash)->first();
        abort_unless($initial, 401);
        $data = $request->validate(['uid' => ['required', 'string', 'regex:/\A(?:[a-fA-F0-9]{8}|[a-fA-F0-9]{14}|[a-fA-F0-9]{20})\z/']]);

        try {
            if ($initial->scope_type === 'koperasi') {
                return $this->handleKoperasiTap($initial, $hash, $data['uid']);
            }

            return $this->handleKegiatanTap($initial, $hash, $data['uid']);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['uid' => 'Kartu atau warga sudah terdaftar. Silakan periksa kembali.']);
        }
    }

    private function handleKegiatanTap(RfidSession $initial, string $hash, string $uid)
    {
        return DB::transaction(function () use ($initial, $hash, $uid) {
            // Same lock order as open/close/enrollment; recheck token after acquiring locks.
            $kegiatan = Kegiatan::whereKey($initial->kegiatan_id)->lockForUpdate()->firstOrFail();
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($session->token_hash, $hash) && $session->expires_at->isFuture(), 401);
            $actor = User::find($session->created_by);
            abort_unless($actor && RfidAccess::canManage($actor, $kegiatan), 403);
            abort_if(in_array($kegiatan->effective_status, ['selesai', 'dibatalkan'], true), 409, 'Kegiatan sudah ditutup.');
            $uidHash = RfidCard::hashUid($uid);

            if ($session->enrollment_user_id) {
                if (! $session->enrollment_expires_at?->isFuture()) {
                    throw ValidationException::withMessages(['uid' => 'Pendaftaran kedaluwarsa. Pengurus harus membatalkan atau memulai ulang.']);
                }
                $user = RfidAccess::participants($kegiatan)->whereKey($session->enrollment_user_id)->lockForUpdate()->firstOrFail();
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

    private function handleKoperasiTap(RfidSession $initial, string $hash, string $uid)
    {
        return DB::transaction(function () use ($initial, $hash, $uid) {
            $session = RfidSession::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            abort_unless($session->scope_type === 'koperasi' && hash_equals($session->token_hash, $hash) && $session->expires_at->isFuture(), 401);
            $actor = User::find($session->created_by);
            abort_unless($actor && RfidAccess::canManageKoperasi($actor) && RfidAccess::matchesKoperasiScope($actor, $session), 403);
            $uidHash = RfidCard::hashUid($uid);

            if ($session->enrollment_user_id) {
                if (! $session->enrollment_expires_at?->isFuture()) {
                    throw ValidationException::withMessages(['uid' => 'Pendaftaran kedaluwarsa. Pengurus harus membatalkan atau memulai ulang.']);
                }
                $user = RfidAccess::koperasiParticipants($session)->whereKey($session->enrollment_user_id)->lockForUpdate()->firstOrFail();
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
}
