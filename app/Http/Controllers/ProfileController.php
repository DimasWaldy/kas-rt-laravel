<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\KartuKeluarga;
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
        ]);
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

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated);

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

}
