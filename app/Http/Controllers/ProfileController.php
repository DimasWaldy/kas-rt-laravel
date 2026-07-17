<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\KartuKeluarga;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
            'rumahs' => $this->profileRumahQuery($request->user())->orderBy('kode_rumah')->get(),
            'rts' => $this->profileRtQuery($request->user())->with('rw')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validated();

        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rt_id'] = $this->resolveProfileRtId($validated, $user);
        $validated['rumah_id'] = $this->resolveRumahId($validated, $user);
        if ($validated['rumah_id']) {
            $rumah = Rumah::findOrFail($validated['rumah_id']);
            $validated['rt_id'] = $rumah->rt_id ?: $validated['rt_id'];

            if (! $rumah->rt_id && $validated['rt_id']) {
                $rumah->update(['rt_id' => $validated['rt_id']]);
            }
        }
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        if (
            $validated['is_penanggung_jawab_rumah']
            && $this->rumahHasDifferentPenanggungJawab($validated['rumah_id'], $user)
        ) {
            return Redirect::route('profile.edit')
                ->withInput()
                ->with('error', 'Rumah ini sudah memiliki penanggung jawab iuran. Hubungi admin/RT untuk mengganti penanggung jawab.');
        }

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
                'rumah_id' => $validated['rumah_id'] ?? $user->rumah_id,
                'rt_id' => $validated['rt_id'] ?? $user->rt_id,
                'is_penanggung_jawab_rumah' => $validated['is_penanggung_jawab_rumah'] ?? $user->is_penanggung_jawab_rumah,
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated);

            $this->syncPenanggungJawabRumah($user);
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = auth()->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function resolveRumahId(array $data, User $user): ?int
    {
        if (! empty($data['rumah_id'])) {
            return $this->profileRumahQuery($user)->findOrFail($data['rumah_id'])->id;
        }

        if (blank($data['rumah_kode'] ?? null)) {
            return null;
        }

        $kodeRumah = strtoupper(trim($data['rumah_kode']));
        $rumah = $this->profileRumahQuery($user)->where('kode_rumah', $kodeRumah)->first();

        if (! $rumah && Rumah::where('kode_rumah', $kodeRumah)->exists()) {
            throw ValidationException::withMessages([
                'rumah_kode' => 'Kode rumah tersebut sudah digunakan di RT lain.',
            ]);
        }

        $rumah ??= Rumah::create([
                'kode_rumah' => $kodeRumah,
                'alamat' => $data['rumah_alamat'] ?? null,
                'rt_id' => $data['rt_id'] ?? $user->rt_id,
            ]);

        if (filled($data['rumah_alamat'] ?? null) && blank($rumah->alamat)) {
            $rumah->update(['alamat' => $data['rumah_alamat']]);
        }

        return $rumah->id;
    }

    private function profileRumahQuery(User $user)
    {
        $query = Rumah::query();

        if ($user->canAccessAllRts()) {
            return $query;
        }

        if ($user->role_name === 'warga') {
            return $query->where('status', 'aktif');
        }

        if ($user->rt_id) {
            return $query->where('rt_id', $user->rt_id);
        }

        return $query->where('status', 'aktif');
    }

    private function profileRtQuery(User $user)
    {
        $query = Rt::query()->where('is_active', true);

        if ($user->canAccessAllRts() || $user->role_name === 'warga') {
            return $query;
        }

        if ($user->rt_id) {
            return $query->whereKey($user->rt_id);
        }

        return $query;
    }

    private function resolveProfileRtId(array $data, User $user): ?int
    {
        if (! empty($data['rt_id'])) {
            return $this->profileRtQuery($user)->findOrFail($data['rt_id'])->id;
        }

        return $user->rt_id;
    }

    private function syncDataWarga(User $user, array $validated): void
    {
        $warga = $user->warga()->firstOrCreate([], [
            'nama_lengkap' => $user->name,
        ]);

        $data = [
            'nama_lengkap' => $user->name,
            'nik' => $validated['nik'] ?? $warga->nik,
            'status_dalam_kk' => $validated['status_dalam_kk'] ?? $warga->status_dalam_kk,
        ];

        if (filled($validated['no_kk'] ?? null)) {
            $kartuKeluarga = KartuKeluarga::firstOrCreate(
                ['no_kk' => $validated['no_kk']],
                [
                    'rumah_id' => $user->rumah_id,
                    'nama_kepala_keluarga' => ($validated['status_dalam_kk'] ?? $warga->status_dalam_kk) === 'kepala_keluarga'
                        ? $user->name
                        : $user->name,
                ]
            );

            if ($kartuKeluarga->rumah_id && $user->rumah_id && $kartuKeluarga->rumah_id !== $user->rumah_id) {
                throw ValidationException::withMessages([
                    'no_kk' => 'Nomor KK tersebut sudah terdaftar di rumah lain.',
                ]);
            }

            if (! $kartuKeluarga->rumah_id && $user->rumah_id) {
                $kartuKeluarga->update(['rumah_id' => $user->rumah_id]);
            }

            if (($validated['status_dalam_kk'] ?? $warga->status_dalam_kk) === 'kepala_keluarga') {
                $kartuKeluarga->update(['nama_kepala_keluarga' => $user->name]);
            }

            $data['kartu_keluarga_id'] = $kartuKeluarga->id;
        }

        $warga->update($data);
    }

    private function syncPenanggungJawabRumah(User $user): void
    {
        if (! $user->rumah_id) {
            return;
        }

        if ($user->is_penanggung_jawab_rumah) {
            User::where('rumah_id', $user->rumah_id)
                ->whereKeyNot($user->id)
                ->update(['is_penanggung_jawab_rumah' => false]);

            Rumah::whereKey($user->rumah_id)->update([
                'penanggung_jawab_id' => $user->id,
                'rt_id' => $user->rt_id,
            ]);
        } elseif (Rumah::whereKey($user->rumah_id)->where('penanggung_jawab_id', $user->id)->exists()) {
            Rumah::whereKey($user->rumah_id)->update(['penanggung_jawab_id' => null]);
        }
    }

    private function rumahHasDifferentPenanggungJawab(?int $rumahId, User $user): bool
    {
        if (! $rumahId) {
            return false;
        }

        return Rumah::whereKey($rumahId)
            ->whereNotNull('penanggung_jawab_id')
            ->where('penanggung_jawab_id', '!=', $user->id)
            ->exists();
    }
}
