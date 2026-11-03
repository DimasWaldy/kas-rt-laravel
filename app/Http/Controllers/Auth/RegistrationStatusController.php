<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationStatusController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->isAktif()) {
            return redirect()->route('dashboard');
        }
        abort_unless(in_array($user->status_akun, ['pending_verifikasi', 'ditolak'], true), 403);
        $warga = $user->warga;
        $canResubmit = $user->status_akun === 'ditolak' && $user->role_name === 'warga'
            && $warga?->status_verifikasi === 'ditolak';

        return response()->view('auth.registration-status', compact('user', 'warga', 'canResubmit'))
            ->header('Cache-Control', 'no-store');
    }

    public function resubmit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[0-9]{10,13}$/'],
            'klarifikasi' => ['required', 'string', 'min:10', 'max:1000'],
            'email' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role_id' => ['prohibited'],
            'status_akun' => ['prohibited'],
            'status_verifikasi' => ['prohibited'],
            'rt_id' => ['prohibited'],
            'rumah_id' => ['prohibited'],
            'kartu_keluarga_id' => ['prohibited'],
            'is_penanggung_jawab_rumah' => ['prohibited'],
        ], [
            'phone.regex' => 'Nomor HP harus berisi 10 sampai 13 digit angka.',
            'klarifikasi.required' => 'Jelaskan perbaikan atau klarifikasi untuk pengurus.',
            'klarifikasi.min' => 'Klarifikasi minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($request, $data) {
            // Same lock order as officer review. A duplicate or stale submit cannot reset approval.
            $warga = Warga::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->role_name === 'warga', 403);
            if ($user->status_akun !== 'ditolak' || $warga->status_verifikasi !== 'ditolak') {
                throw ValidationException::withMessages(['pendaftaran' => 'Pengajuan sudah diproses atau sedang diperiksa. Muat ulang halaman status.']);
            }

            $oldValues = [
                'name' => $user->name, 'phone' => $user->phone,
                'status_akun' => $user->status_akun, 'status_verifikasi' => $warga->status_verifikasi,
                'catatan_verifikasi' => $warga->catatan_verifikasi,
                'diverifikasi_oleh' => $warga->diverifikasi_oleh, 'diverifikasi_at' => $warga->diverifikasi_at,
            ];
            $user->update(['name' => $data['name'], 'phone' => $data['phone'], 'status_akun' => 'pending_verifikasi']);
            $warga->update([
                'nama_lengkap' => $data['name'], 'status_verifikasi' => 'pending',
                'metode_verifikasi' => null, 'catatan_verifikasi' => null,
                'diverifikasi_oleh' => null, 'diverifikasi_at' => null,
            ]);
            AuditLog::create([
                'user_id' => $user->id, 'auditable_type' => Warga::class, 'auditable_id' => $warga->id,
                'event' => 'pendaftaran_diajukan_ulang', 'old_values' => $oldValues,
                'new_values' => ['name' => $user->name, 'phone' => $user->phone,
                    'status_akun' => $user->status_akun, 'status_verifikasi' => $warga->status_verifikasi],
                'notes' => $data['klarifikasi'], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
            ]);
        }, 3);

        return redirect()->route('pendaftaran.show')->with('success', 'Perbaikan terkirim. Pendaftaran Anda menunggu pemeriksaan ulang oleh pengurus.');
    }
}
