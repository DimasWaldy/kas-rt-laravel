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

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usersQuery = User::with('rumah')->whereRelation('role', 'name', 'warga');

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

        return view('admin.warga.index', compact('users'));
    }

    public function edit(User $user): View
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        $rumahs = Rumah::orderBy('kode_rumah')->get();

        return view('admin.warga.edit', compact('user', 'rumahs'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'rumah_kode' => ['nullable', 'string', 'max:50'],
            'rumah_alamat' => ['nullable', 'string', 'max:500'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:25'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'jumlah_anggota_keluarga' => ['nullable', 'integer', 'min:0'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
        ]);

        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated);
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

    public function create(): View
    {
        $rumahs = Rumah::orderBy('kode_rumah')->get();

        return view('admin.warga.create', compact('rumahs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'rumah_kode' => ['nullable', 'string', 'max:50'],
            'rumah_alamat' => ['nullable', 'string', 'max:500'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:25'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'jumlah_anggota_keluarga' => ['nullable', 'integer', 'min:0'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = Role::firstOrCreate(
            ['name' => 'warga'],
            ['description' => 'Warga']
        )->id;
        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated);
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $this->syncPenanggungJawabRumah($user);
        });

        return redirect()->route('admin.warga.index')->with('success', 'Warga baru berhasil ditambahkan.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.warga.index')->with('success', "Warga '{$userName}' berhasil dihapus.");
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
}
