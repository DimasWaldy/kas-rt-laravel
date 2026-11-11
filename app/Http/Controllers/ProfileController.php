<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
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
            abort_unless($user->isAktif(), 403, 'Akun belum aktif.');

            $rtId = $request->query('rt_id');
            if (! $rtId) {
                return response()->json(['rumahs' => []]);
            }

            abort_unless($user->isGlobalOperator() || (int) $user->rt_id === (int) $rtId, 403, 'RT di luar wilayah akun.');

            $rumahs = Rumah::where('status', 'aktif')
                ->where('rt_id', $rtId)
                ->orderBy('kode_rumah')
                ->get(['id', 'rt_id', 'kode_rumah', 'alamat']);

            return response()->json(['rumahs' => $rumahs]);
        }

        $user->loadMissing('warga.kartuKeluarga', 'rt', 'rumah.penanggungJawab');
        $wargaDomisili = null;
        $rumahsDomisili = collect();
        $rts = collect();

        if ($user->role_name === 'warga') {
            $wargaDomisili = $user->warga;
            $wargaDomisili?->load('rumahDiajukan');
            $aktifRtId = $user->rt_id;
            $rts = Rt::whereKey($aktifRtId)
                ->where('is_active', true)
                ->whereHas('rw', fn ($q) => $q->where('is_active', true))
                ->with('rw:id,name')
                ->orderBy('name')
                ->get(['id', 'rw_id', 'name']);
            if ($rts->isEmpty()) {
                $rts = Rt::where('is_active', true)
                    ->whereHas('rw', fn ($q) => $q->where('is_active', true))
                    ->with('rw:id,name')
                    ->orderBy('name')
                    ->get(['id', 'rw_id', 'name']);
            }
            $selectedRtId = request('rt_id', old('rt_id', $aktifRtId ?? $rts->first()?->id));
            $statusPending = $wargaDomisili?->status_domisili === 'pending';
            // Verified residents get the house list through the scoped JSON endpoint so a
            // plain profile render never exposes other households' addresses server-side.
            $rumahsDomisili = ($statusPending || ! $user->onboarding_domisili || $user->rumah_id)
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

        $domicileSubmitted = false;

        DB::transaction(function () use ($request, $user, $validated, &$domicileSubmitted) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
            ]);

            $isWarga = $user->role_name === 'warga';
            $domicileSubmitted = $isWarga && isset($validated['mode']);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated);

            if ($domicileSubmitted) {
                $this->syncPengajuanDomisili($user, $validated);
            } elseif ($isWarga && $user->rumah_id && array_key_exists('ajukan_pj_iuran', $validated)) {
                $this->syncPenanggungJawab($request, $user, (bool) $validated['ajukan_pj_iuran']);
            }
        });

        $successMessage = match (true) {
            $user->role_name === 'warga' && $user->onboarding_domisili => 'Profil dan pengajuan verifikasi berhasil dikirim. Menunggu pemeriksaan dan konfirmasi oleh pengurus RT/RW.',
            $domicileSubmitted => 'Profil disimpan dan pengajuan perubahan domisili berhasil dikirim untuk diperiksa pengurus RT/RW.',
            default => 'Profil berhasil diperbarui.',
        };

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('success', $successMessage);
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
        $isWarga = $user->role_name === 'warga';

        if ($isOnboardingDomisili && ! empty($validated['no_kk'])) {
            $noKk = trim($validated['no_kk']);
            $isKepala = ($validated['status_dalam_kk'] ?? null) === 'kepala_keluarga';
            $defaultKepala = $isKepala ? $user->name : ($warga->nama_lengkap ?? $user->name ?? 'Belum ditetapkan');

            $kk = KartuKeluarga::where('no_kk', $noKk)->first();
            if ($kk && $kk->wargas()->where('user_id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'no_kk' => 'KK tersebut sudah memiliki anggota. Penetapan anggota keluarga harus melalui pengurus.',
                ]);
            }
            if ($kk && $kk->rumah_id && (int) $kk->rumah_id !== (int) ($validated['rumah_id'] ?? 0)) {
                throw ValidationException::withMessages([
                    'no_kk' => 'KK tersebut sudah terhubung ke rumah lain. Penetapan KK harus melalui pengurus.',
                ]);
            }

            $kk ??= KartuKeluarga::create([
                'no_kk' => $noKk,
                'nama_kepala_keluarga' => $defaultKepala,
            ]);
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

        if ($isWarga) {
            if ($isOnboardingDomisili || array_key_exists('ajukan_pj_iuran', $validated)) {
                $data['ajukan_pj_iuran'] = ! empty($validated['ajukan_pj_iuran']);
            }
            if (array_key_exists('jumlah_penghuni_rumah', $validated)) {
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

    private function syncPenanggungJawab(Request $request, User $user, bool $jadiPj): void
    {
        if (! $user->isAktif() || ! $user->rumah_id) {
            return;
        }

        $rumah = Rumah::whereKey($user->rumah_id)->lockForUpdate()->first();
        if (! $rumah || $rumah->status !== 'aktif' || (int) $user->rt_id !== (int) $rumah->rt_id) {
            return;
        }

        $oldPjId = $rumah->penanggung_jawab_id;

        if ($jadiPj) {
            $existingPj = $oldPjId ? User::whereKey($oldPjId)->lockForUpdate()->first() : null;
            $hasOtherActivePj = $existingPj
                && (int) $existingPj->id !== (int) $user->id
                && $existingPj->isAktif()
                && (int) $existingPj->rumah_id === (int) $rumah->id;

            if ($hasOtherActivePj) {
                $user->warga?->update(['ajukan_pj_iuran' => false]);

                return;
            }

            if ($existingPj && (int) $existingPj->id !== (int) $user->id && $existingPj->is_penanggung_jawab_rumah) {
                $existingPj->is_penanggung_jawab_rumah = false;
                $existingPj->save();
            }

            $rumah->update(['penanggung_jawab_id' => $user->id]);
            $user->is_penanggung_jawab_rumah = true;
            $user->save();

            if ((int) $oldPjId !== (int) $user->id) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'auditable_type' => Rumah::class,
                    'auditable_id' => $rumah->id,
                    'event' => 'pj_iuran_ditetapkan',
                    'old_values' => ['penanggung_jawab_id' => $oldPjId],
                    'new_values' => ['penanggung_jawab_id' => $user->id],
                    'notes' => 'Warga menetapkan diri sebagai penanggung jawab iuran rumah melalui profil.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } else {
            if ((int) $oldPjId === (int) $user->id) {
                $rumah->update(['penanggung_jawab_id' => null]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'auditable_type' => Rumah::class,
                    'auditable_id' => $rumah->id,
                    'event' => 'pj_iuran_dilepas',
                    'old_values' => ['penanggung_jawab_id' => $oldPjId],
                    'new_values' => ['penanggung_jawab_id' => null],
                    'notes' => 'Warga melepaskan status penanggung jawab iuran rumah melalui profil.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            if ($user->is_penanggung_jawab_rumah) {
                $user->is_penanggung_jawab_rumah = false;
                $user->save();
            }
        }
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
            if ($user->rumah_id && (int) $rumah->id === (int) $user->rumah_id) {
                throw ValidationException::withMessages([
                    'rumah_id' => 'Anda sudah terdaftar di rumah tersebut.',
                ]);
            }
            $alamat = $rumah->alamat;
        } else {
            $alamat = trim($validated['alamat']);
            $rumah = Rumah::where('rt_id', $rt->id)->where('status', 'aktif')
                ->whereRaw('LOWER(TRIM(alamat)) = ?', [mb_strtolower($alamat)])->first();
            if ($user->rumah_id && $rumah && (int) $rumah->id === (int) $user->rumah_id) {
                throw ValidationException::withMessages([
                    'alamat' => 'Alamat tersebut adalah rumah Anda saat ini.',
                ]);
            }
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
