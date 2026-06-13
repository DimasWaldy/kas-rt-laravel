<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rumah;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usersQuery = User::with('rumah')
            ->visibleTo($request->user())
            ->whereRelation('role', 'name', 'warga');

        if ($search = trim($request->input('search'))) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('no_kk', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('rt', 'like', "%{$search}%")
                    ->orWhere('rw', 'like', "%{$search}%")
                    ->orWhereHas('rumah', function ($rumahQuery) use ($search) {
                        $rumahQuery->where('kode_rumah', 'like', "%{$search}%")
                            ->orWhere('alamat', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('filter_head')) {
            if ($request->input('filter_head') === 'kepala') {
                $usersQuery->where('is_kepala_keluarga', true);
            } elseif ($request->input('filter_head') === 'warga') {
                $usersQuery->where('is_kepala_keluarga', false);
            }
        }

        $users = $usersQuery->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $rumahs = Rumah::visibleTo($request->user())->orderBy('kode_rumah')->get();

        return view('admin.warga.index', compact('users', 'rumahs'));
    }

    public function edit(Request $request, User $user): View
    {
        if ($user->role_name !== 'warga' || ! User::visibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $rumahs = Rumah::visibleTo($request->user())->orderBy('kode_rumah')->get();

        return view('admin.warga.edit', compact('user', 'rumahs'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga' || ! User::visibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $validated = $request->validate($this->wargaRules($user), $this->wargaMessages());

        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated, $request->user());
        $validated['rt_id'] = $this->resolveRtId($validated['rumah_id'], $request->user(), $user->rt_id);
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        DB::transaction(function () use ($user, $validated) {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncPenanggungJawabRumah($user);
        });

        return redirect()->route('admin.warga.index')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function create(Request $request): View
    {
        $rumahs = Rumah::visibleTo($request->user())->orderBy('kode_rumah')->get();

        return view('admin.warga.create', compact('rumahs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->wargaRules(), $this->wargaMessages());

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = Role::firstOrCreate(
            ['name' => 'warga'],
            ['description' => 'Warga']
        )->id;
        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated, $request->user());
        $validated['rt_id'] = $this->resolveRtId($validated['rumah_id'], $request->user());
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $this->syncPenanggungJawabRumah($user);
        });

        return redirect()->route('admin.warga.index')->with('success', 'Warga baru berhasil ditambahkan.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga' || ! User::visibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.warga.index')->with('success', "Warga '{$userName}' berhasil dihapus.");
    }

    private function resolveRumahId(array $data, User $actor): ?int
    {
        if (! empty($data['rumah_id'])) {
            return Rumah::visibleTo($actor)->findOrFail($data['rumah_id'])->id;
        }

        if (blank($data['rumah_kode'] ?? null)) {
            return null;
        }

        $kodeRumah = strtoupper(trim($data['rumah_kode']));
        $rumah = Rumah::visibleTo($actor)->where('kode_rumah', $kodeRumah)->first();

        if (! $rumah && Rumah::where('kode_rumah', $kodeRumah)->exists()) {
            throw ValidationException::withMessages([
                'rumah_kode' => 'Kode rumah tersebut sudah digunakan di RT lain.',
            ]);
        }

        $rumah ??= Rumah::create([
                'kode_rumah' => $kodeRumah,
                'alamat' => $data['rumah_alamat'] ?? null,
                'rt' => $data['rt'] ?? null,
                'rw' => $data['rw'] ?? null,
                'rt_id' => $actor->canAccessAllRts() ? null : $actor->rt_id,
            ]);

        if (filled($data['rumah_alamat'] ?? null) && blank($rumah->alamat)) {
            $rumah->update(['alamat' => $data['rumah_alamat']]);
        }

        return $rumah->id;
    }

    private function resolveRtId(?int $rumahId, User $actor, ?int $currentRtId = null): ?int
    {
        if ($rumahId) {
            return Rumah::findOrFail($rumahId)->rt_id;
        }

        return $actor->canAccessAllRts() ? $currentRtId : $actor->rt_id;
    }

    private function wargaRules(?User $user = null): array
    {
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($userId ? ',' . $userId : '')],
            'password' => [$user ? 'sometimes' : 'required', 'string', 'min:8'],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'rumah_kode' => ['nullable', 'string', 'max:50'],
            'rumah_alamat' => ['nullable', 'string', 'max:500'],
            'no_kk' => ['nullable', 'digits:16'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,13}$/'],
            'rt' => ['nullable', 'regex:/^[0-9]{1,3}$/'],
            'rw' => ['nullable', 'regex:/^[0-9]{1,3}$/'],
            'jumlah_anggota_keluarga' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
        ];
    }

    private function wargaMessages(): array
    {
        return [
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'rt.regex' => 'RT harus berisi angka saja, maksimal 3 digit.',
            'rw.regex' => 'RW harus berisi angka saja, maksimal 3 digit.',
            'jumlah_anggota_keluarga.min' => 'Jumlah anggota keluarga minimal 1 orang.',
            'jumlah_anggota_keluarga.max' => 'Jumlah anggota keluarga maksimal 20 orang.',
        ];
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
                'rt' => $user->getRawOriginal('rt'),
                'rw' => $user->rw,
            ]);
        } elseif (Rumah::whereKey($user->rumah_id)->where('penanggung_jawab_id', $user->id)->exists()) {
            Rumah::whereKey($user->rumah_id)->update(['penanggung_jawab_id' => null]);
        }
    }
}
