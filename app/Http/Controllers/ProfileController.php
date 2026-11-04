<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\KartuKeluarga;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form or return JSON for rumah options.
     */
    public function edit(Request $request): View|JsonResponse
    {
        $user = auth()->user();

        if ($request->wantsJson()) {
            $rtId = $request->query('rt_id');
            if (! $rtId) {
                return response()->json(['rumahs' => []]);
            }

            $rumahs = Rumah::where('status', 'aktif')
                ->where('rt_id', $rtId)
                ->orderBy('kode_rumah')
                ->get(['id', 'rt_id', 'kode_rumah', 'alamat']);

            return response()->json(['rumahs' => $rumahs]);
        }

        $user->loadMissing('warga.kartuKeluarga', 'rt', 'rumah');
        $wargaDomisili = null;
        $rumahsDomisili = collect();
        $rts = collect();

        if ($user->role_name === 'warga' && $user->onboarding_domisili && ! $user->rumah_id) {
            $wargaDomisili = $user->warga;
            $wargaDomisili?->load('rumahDiajukan');

            $rts = Rt::where('is_active', true)
                ->whereHas('rw', fn ($q) => $q->where('is_active', true))
                ->with('rw:id,name')
                ->orderBy('name')
                ->get(['id', 'rw_id', 'name']);

            $selectedRtId = request('rt_id', old('rt_id', $user->rt_id ?? $rts->first()?->id));
            $rumahsDomisili = ($wargaDomisili?->status_domisili === 'pending' || ! $selectedRtId)
                ? collect()
                : Rumah::where('status', 'aktif')
                    ->where('rt_id', $selectedRtId)
                    ->orderBy('kode_rumah')
                    ->get(['id', 'rt_id', 'kode_rumah', 'alamat']);
        }

        return view('profile.edit', compact('user', 'wargaDomisili', 'rumahsDomisili', 'rts'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
            ]);

            $isOnboardingDomisili = $user->role_name === 'warga'
                && $user->onboarding_domisili
                && ! $user->rumah_id;

            if ($isOnboardingDomisili && isset($validated['rt_id'])) {
                $user->rt_id = $validated['rt_id'];
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated);

            if ($isOnboardingDomisili && isset($validated['mode'])) {
                $this->syncPengajuanDomisili($user, $validated);
            }
        });

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('success', $user->role_name === 'warga' && $user->onboarding_domisili
                ? 'Profil dan pengajuan verifikasi berhasil dikirim. Menunggu pemeriksaan dan konfirmasi oleh pengurus RT/RW.'
                : 'Profil berhasil diperbarui.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'string', 'in:konfirmasi,KONFIRMASI'],
        ], [
            'confirmation.required' => 'Ketik "konfirmasi" untuk menyetujui penghapusan akun.',
            'confirmation.in' => 'Ketik kata "konfirmasi" dengan tepat untuk menghapus akun.',
        ]);

        $user = auth()->user();

        try {
            DB::transaction(function () use ($user) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail()->delete();
            });
        } catch (ValidationException $exception) {
            throw $exception->errorBag('userDeletion');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function syncDataWarga(User $user, array $validated): void
    {
        $warga = $user->warga()->firstOrCreate([], [
            'nama_lengkap' => $user->name,
        ]);

        $data = [
            'nama_lengkap' => $user->name,
            'nik' => $validated['nik'] ?? $warga->nik,
        ];

        $isOnboardingDomisili = $user->role_name === 'warga'
            && $user->onboarding_domisili
            && ! $user->rumah_id;

        if ($isOnboardingDomisili && ! empty($validated['no_kk'])) {
            $noKk = trim($validated['no_kk']);
            $isKepala = ($validated['status_dalam_kk'] ?? null) === 'kepala_keluarga';
            $defaultKepala = $isKepala ? $user->name : ($warga->nama_lengkap ?? $user->name ?? 'Belum ditetapkan');

            $kk = KartuKeluarga::firstOrCreate(
                ['no_kk' => $noKk],
                ['nama_kepala_keluarga' => $defaultKepala]
            );
            $data['kartu_keluarga_id'] = $kk->id;
            $data['status_dalam_kk'] = $validated['status_dalam_kk'] ?? $warga->status_dalam_kk ?? 'anggota';

            if ($data['status_dalam_kk'] === 'kepala_keluarga' && (empty($kk->nama_kepala_keluarga) || $kk->nama_kepala_keluarga === 'Belum ditetapkan')) {
                $kk->update(['nama_kepala_keluarga' => $user->name]);
            }

            if (! empty($validated['jumlah_anggota'])) {
                $kk->update(['jumlah_anggota' => (int) $validated['jumlah_anggota']]);
            }
        } elseif ($isOnboardingDomisili && ! empty($validated['status_dalam_kk'])) {
            $data['status_dalam_kk'] = $validated['status_dalam_kk'];
        }

        if ($isOnboardingDomisili) {
            $data['ajukan_pj_iuran'] = ! empty($validated['ajukan_pj_iuran']);
            if (isset($validated['jumlah_penghuni_rumah'])) {
                $data['jumlah_penghuni_rumah'] = $validated['jumlah_penghuni_rumah'] ? (int) $validated['jumlah_penghuni_rumah'] : null;
            }
        }

        if (request()->hasFile('dokumen_ktp')) {
            if ($warga->dokumen_ktp && Storage::disk('local')->exists($warga->dokumen_ktp)) {
                Storage::disk('local')->delete($warga->dokumen_ktp);
            }
            $data['dokumen_ktp'] = request()->file('dokumen_ktp')->store('dokumen_verifikasi', 'local');
        }

        if (request()->hasFile('dokumen_kk')) {
            if ($warga->dokumen_kk && Storage::disk('local')->exists($warga->dokumen_kk)) {
                Storage::disk('local')->delete($warga->dokumen_kk);
            }
            $data['dokumen_kk'] = request()->file('dokumen_kk')->store('dokumen_verifikasi', 'local');
        }

        // Family membership and the official family head are managed by officers.
        $warga->update($data);
    }

    private function syncPengajuanDomisili(User $user, array $validated): void
    {
        $warga = Warga::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

        if ($warga->status_domisili === 'pending') {
            return;
        }

        $rtId = $validated['rt_id'] ?? $user->rt_id;
        $rt = Rt::whereKey($rtId)->where('is_active', true)
            ->whereHas('rw', fn ($q) => $q->where('is_active', true))->firstOrFail();

        if ($validated['mode'] === 'existing') {
            $rumah = Rumah::whereKey($validated['rumah_id'])->where('rt_id', $rt->id)
                ->where('status', 'aktif')->firstOrFail();
            $alamat = $rumah->alamat;
        } else {
            $alamat = trim($validated['alamat']);
            $rumah = Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
                ->whereRaw('LOWER(TRIM(alamat)) = ?', [mb_strtolower($alamat)])->first();
        }

        $warga->update([
            'rumah_diajukan_id' => $rumah?->id,
            'mode_domisili' => $validated['mode'],
            'alamat_domisili' => $alamat,
            'status_domisili' => 'pending',
            'catatan_domisili' => null,
            'domisili_diajukan_at' => now(),
            'domisili_diproses_oleh' => null,
            'domisili_diproses_at' => null,
        ]);
    }
}
