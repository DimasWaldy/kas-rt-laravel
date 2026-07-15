<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warga;
use App\Notifications\WargaTerverifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiWargaController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $this->authorizePetugas($request);

        $wargas = Warga::query()
            ->with(['user'])
            ->where('status_verifikasi', 'pending')
            ->orderBy('created_at')
            ->get();

        return view('verifikasi_warga.index', compact('wargas'));
    }

    public function show(Request $request, Warga $warga): View
    {
        $this->authorizePetugas($request);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $warga->load(['user']);

        return view('verifikasi_warga.show', compact('warga'));
    }

    public function dokumen(Request $request, Warga $warga, string $jenis): StreamedResponse
    {
        $this->authorizePetugas($request);

        $path = match ($jenis) {
            'kk' => $warga->dokumen_kk,
            'ktp' => $warga->dokumen_ktp,
            default => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function verifikasi(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'metode_verifikasi' => ['required', 'in:tatap_muka,dokumen'],
        ]);

        $user = DB::transaction(function () use ($validated, $warga, $actor) {
            $lockedWarga = Warga::query()
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWarga->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'status_verifikasi' => 'Calon warga ini sudah selesai diproses.',
                ]);
            }

            $lockedWarga->update([
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => $validated['metode_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $user = $lockedWarga->user()->firstOrFail();
            $user->update([
                'status_akun' => 'aktif',
            ]);

            return $user;
        });

        $user->notify(new WargaTerverifikasi());

        return redirect()
            ->route('verifikasi-warga.index')
            ->with('success', 'Warga berhasil diverifikasi dan akunnya telah diaktifkan.');
    }

    public function tolak(Request $request, Warga $warga): RedirectResponse
    {
        $actor = $this->authorizePetugas($request);

        abort_unless($warga->status_verifikasi === 'pending', 404);

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string', 'min:10'],
        ], [
            'catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.',
            'catatan_verifikasi.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($validated, $warga, $actor) {
            $lockedWarga = Warga::query()
                ->whereKey($warga->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWarga->status_verifikasi !== 'pending') {
                throw ValidationException::withMessages([
                    'status_verifikasi' => 'Calon warga ini sudah selesai diproses.',
                ]);
            }

            $lockedWarga->update([
                'status_verifikasi' => 'ditolak',
                'catatan_verifikasi' => $validated['catatan_verifikasi'],
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
            ]);

            $lockedWarga->user()->update([
                'status_akun' => 'ditolak',
            ]);
        });

        return redirect()
            ->route('verifikasi-warga.index')
            ->with('success', 'Pengajuan warga telah ditolak.');
    }

    private function authorizePetugas(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor && $actor->canManageWarga(), 403);

        return $actor;
    }
}
