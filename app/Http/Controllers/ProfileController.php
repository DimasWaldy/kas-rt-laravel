<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Rumah;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
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
            'rumahs' => Rumah::orderBy('kode_rumah')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validated();

        if ($request->has('no_kk')) {
            $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');
        }

        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated);
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
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

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

    private function resolveRumahId(array $data): ?int
    {
        if (! empty($data['rumah_id'])) {
            return (int) $data['rumah_id'];
        }

        if (blank($data['rumah_kode'] ?? null)) {
            return null;
        }

        $rumah = Rumah::firstOrCreate(
            ['kode_rumah' => strtoupper(trim($data['rumah_kode']))],
            [
                'alamat' => $data['rumah_alamat'] ?? null,
                'rt' => $data['rt'] ?? null,
                'rw' => $data['rw'] ?? null,
            ]
        );

        if (filled($data['rumah_alamat'] ?? null) && blank($rumah->alamat)) {
            $rumah->update(['alamat' => $data['rumah_alamat']]);
        }

        return $rumah->id;
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
                'rt' => $user->rt,
                'rw' => $user->rw,
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
