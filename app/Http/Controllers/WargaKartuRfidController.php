<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WargaKartuRfidController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $card = RfidCard::where('user_id', $user->id)
            ->with(['verifiedRt:id,name'])
            ->first();

        $activeSession = null;
        if ($user->rt_id) {
            $activeSession = RfidSession::whereIn('scope_key', [
                'warga:rt:'.$user->rt_id,
                'warga:global',
            ])
                ->where('expires_at', '>', now())
                ->first();
        }

        $isEnrolled = $activeSession && (int) $activeSession->enrollment_user_id === (int) $user->id && $activeSession->enrollment_expires_at?->isFuture();
        $remainingSeconds = $isEnrolled ? max(0, (int) now()->diffInSeconds($activeSession->enrollment_expires_at, false)) : 0;

        return view('warga.kartu_rfid', compact('user', 'card', 'activeSession', 'isEnrolled', 'remainingSeconds'));
    }

    public function openSession(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAktif(), 403);

        if ($user->warga && $user->warga->status_verifikasi !== 'terverifikasi') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun warga Anda harus berstatus terverifikasi terlebih dahulu sebelum dapat mendaftarkan e-KTP.',
                ], 422);
            }

            return back()->with('error', 'Akun warga Anda harus berstatus terverifikasi terlebih dahulu sebelum dapat mendaftarkan e-KTP.');
        }

        if (RfidCard::where('user_id', $user->id)->whereNotNull('ktp_verified_at')->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'e-KTP fisik Anda sudah terdaftar resmi di RT dan sistem.',
                ], 422);
            }

            return back()->with('error', 'e-KTP fisik Anda sudah terdaftar resmi di RT dan sistem.');
        }

        $scopeKey = $user->rt_id ? 'warga:rt:'.$user->rt_id : 'warga:global';
        $rwId = $user->assignedRwId() ?? $user->rt?->rw_id;
        Cache::forget("rfid_enrollment_notice:{$user->id}");

        $session = RfidSession::where('scope_key', $scopeKey)->first();

        if ($session) {
            // Keep existing token so configured ESP32 device continues to work seamlessly
            $session->update([
                'expires_at' => now()->addHours(6),
                'enrollment_user_id' => $user->id,
                'enrollment_expires_at' => now()->addMinutes(3),
            ]);
        } else {
            $creator = User::where('rt_id', $user->rt_id)
                ->whereHas('role', fn ($q) => $q->whereIn('name', ['ketua_rt', 'rt']))
                ->first()
                ?? User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'superadmin', 'admin']))->first()
                ?? $user;

            $token = bin2hex(random_bytes(32));

            RfidSession::create([
                'scope_key' => $scopeKey,
                'scope_type' => 'warga',
                'created_by' => $creator->id,
                'rw_id' => $rwId,
                'rt_id' => $user->rt_id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(6),
                'enrollment_user_id' => $user->id,
                'enrollment_expires_at' => now()->addMinutes(3),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesi tap e-KTP mandiri aktif selama 3 menit. Silakan tempelkan e-KTP Anda ke alat reader RT sekarang.',
                'remaining_seconds' => 180,
            ]);
        }

        return back()->with('success', 'Sesi tap e-KTP mandiri aktif selama 3 menit. Silakan tempelkan e-KTP Anda ke alat reader RT sekarang.');
    }

    public function cancelSession(Request $request)
    {
        $user = $request->user();
        $scopeKey = $user->rt_id ? 'warga:rt:'.$user->rt_id : 'warga:global';

        $session = RfidSession::where('scope_key', $scopeKey)
            ->where('enrollment_user_id', $user->id)
            ->first();

        if ($session) {
            $session->update([
                'enrollment_user_id' => null,
                'enrollment_expires_at' => null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesi tap e-KTP dibatalkan.',
            ]);
        }

        return back()->with('success', 'Sesi tap e-KTP dibatalkan.');
    }

    public function unregister(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAktif(), 403);

        RfidCard::where('user_id', $user->id)->delete();
        Cache::forget("rfid_enrollment_notice:{$user->id}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran kartu e-KTP berhasil dicabut. Anda dapat mendaftarkan kartu fisik baru.',
            ]);
        }

        return back()->with('success', 'Pendaftaran kartu e-KTP berhasil dicabut. Anda dapat mendaftarkan kartu fisik baru.');
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $scopeKey = $user->rt_id ? 'warga:rt:'.$user->rt_id : 'warga:global';

        $session = RfidSession::where('scope_key', $scopeKey)->first();
        $card = RfidCard::where('user_id', $user->id)->first();

        $isEnrolled = $session && (int) $session->enrollment_user_id === (int) $user->id && $session->enrollment_expires_at?->isFuture();
        $remainingSeconds = $isEnrolled ? max(0, (int) now()->diffInSeconds($session->enrollment_expires_at, false)) : 0;
        $notice = Cache::pull("rfid_enrollment_notice:{$user->id}");

        // Cek apakah ESP32 mengirim heartbeat dalam 30 detik terakhir
        $deviceOnline = Cache::has('rfid_device_online');

        return response()->json([
            'is_enrolling' => $isEnrolled,
            'remaining_seconds' => $remainingSeconds,
            'is_verified' => $card && $card->ktp_verified_at !== null,
            'verified_at' => $card?->ktp_verified_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i'),
            'uid_preview' => $card?->uid_hash ? substr($card->uid_hash, 0, 16).'••••' : null,
            'reader_active' => $session && $session->expires_at->isFuture(),
            'device_online' => $deviceOnline,
            'notice' => $notice,
        ]);
    }

    public function tapNfc(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAktif(), 403);

        if ($user->warga && $user->warga->status_verifikasi !== 'terverifikasi') {
            return response()->json([
                'success' => false,
                'message' => 'Akun warga Anda harus berstatus terverifikasi terlebih dahulu sebelum dapat mendaftarkan e-KTP.',
            ], 422);
        }

        if (RfidCard::where('user_id', $user->id)->whereNotNull('ktp_verified_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'e-KTP fisik Anda sudah terdaftar resmi di RT dan sistem.',
            ], 422);
        }

        $rawUid = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', (string) $request->input('uid')));

        if (! in_array(strlen($rawUid), [8, 14, 20])) {
            return response()->json([
                'success' => false,
                'message' => 'Format chip e-KTP tidak valid (panjang UID harus 8, 14, atau 20 karakter hexadesimal).',
            ], 422);
        }

        $uidHash = RfidCard::hashUid($rawUid);

        $existingCard = RfidCard::where('uid_hash', $uidHash)->with('user')->first();
        if ($existingCard) {
            $ownerName = $existingCard->user?->name ?? 'warga lain';

            return response()->json([
                'success' => false,
                'message' => "Nomor chip e-KTP ini sudah terdaftar di akun warga lain: {$ownerName}. Silakan gunakan kartu e-KTP milik Anda sendiri.",
            ], 422);
        }

        $verifierId = User::where('rt_id', $user->rt_id)->whereHas('role', fn ($q) => $q->whereIn('name', ['ketua_rt', 'rt']))->value('id')
            ?? User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'superadmin', 'admin']))->value('id')
            ?? $user->id;

        RfidCard::updateOrCreate(
            ['user_id' => $user->id],
            [
                'uid_hash' => $uidHash,
                'ktp_verified_at' => now(),
                'verified_by' => $verifierId,
                'verified_rt_id' => $user->rt_id,
                'koperasi_verified_at' => now(),
            ]
        );

        $scopeKey = $user->rt_id ? 'warga:rt:'.$user->rt_id : 'warga:global';
        $session = RfidSession::where('scope_key', $scopeKey)
            ->where('enrollment_user_id', $user->id)
            ->first();

        if ($session) {
            $session->update([
                'enrollment_user_id' => null,
                'enrollment_expires_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selamat! Fisik e-KTP Anda berhasil diverifikasi resmi melalui sensor NFC Smartphone.',
        ]);
    }
}
