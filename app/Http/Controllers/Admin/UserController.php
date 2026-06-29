<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KartuKeluarga;
use App\Models\Rumah;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
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
        $usersQuery = User::with(['rumah', 'warga.kartuKeluarga'])
            ->visibleTo($request->user())
            ->whereRelation('role', 'name', 'warga');

        if ($search = trim($request->input('search'))) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('warga', function ($wargaQuery) use ($search) {
                        $wargaQuery->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%")
                            ->orWhereHas('kartuKeluarga', fn ($kkQuery) => $kkQuery->where('no_kk', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('rumah', function ($rumahQuery) use ($search) {
                        $rumahQuery->where('kode_rumah', 'like', "%{$search}%")
                            ->orWhere('alamat', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('filter_head')) {
            if ($request->input('filter_head') === 'kepala') {
                $usersQuery->whereHas('warga', fn ($query) => $query->where('status_dalam_kk', 'kepala_keluarga'));
            } elseif ($request->input('filter_head') === 'warga') {
                $usersQuery->whereHas('warga', fn ($query) => $query->where('status_dalam_kk', 'anggota'));
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

        $statusDalamKk = $request->boolean('is_kepala_keluarga') ? 'kepala_keluarga' : 'anggota';
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated, $request->user());
        $validated['rt_id'] = $this->resolveRtId($validated['rumah_id'], $request->user(), $user->rt_id);
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        DB::transaction(function () use ($user, $validated, $statusDalamKk) {
            $user->fill(collect($validated)->only([
                'name', 'email', 'password', 'phone', 'rumah_id', 'rt_id', 'is_penanggung_jawab_rumah',
            ])->all());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated, $statusDalamKk);
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
        $statusDalamKk = $request->boolean('is_kepala_keluarga') ? 'kepala_keluarga' : 'anggota';
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');
        $validated['rumah_id'] = $this->resolveRumahId($validated, $request->user());
        $validated['rt_id'] = $this->resolveRtId($validated['rumah_id'], $request->user());
        unset($validated['rumah_kode'], $validated['rumah_alamat']);

        DB::transaction(function () use ($validated, $statusDalamKk) {
            $user = User::create(collect($validated)->only([
                'name', 'email', 'password', 'phone', 'role_id', 'rumah_id', 'rt_id', 'is_penanggung_jawab_rumah',
            ])->all() + ['status_akun' => 'aktif']);
            $this->syncDataWarga($user, $validated, $statusDalamKk);
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
            'nik' => ['nullable', 'digits:16', 'unique:wargas,nik' . ($user?->warga ? ',' . $user->warga->id : '')],
            'nama_lengkap' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,13}$/'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'is_penanggung_jawab_rumah' => ['nullable', 'boolean'],
        ];
    }

    private function wargaMessages(): array
    {
        return [
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'nik.digits' => 'NIK harus berisi 16 digit angka.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
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
            ]);
        } elseif (Rumah::whereKey($user->rumah_id)->where('penanggung_jawab_id', $user->id)->exists()) {
            Rumah::whereKey($user->rumah_id)->update(['penanggung_jawab_id' => null]);
        }
    }

    private function syncDataWarga(User $user, array $data, string $statusDalamKk): void
    {
        $kartuKeluargaId = $user->warga?->kartu_keluarga_id;

        if (filled($data['no_kk'] ?? null)) {
            $kartuKeluarga = KartuKeluarga::firstOrCreate(
                ['no_kk' => $data['no_kk']],
                [
                    'rumah_id' => $user->rumah_id,
                    'nama_kepala_keluarga' => $statusDalamKk === 'kepala_keluarga'
                        ? $user->name
                        : $data['nama_kepala_keluarga'] ?? $user->name,
                ]
            );

            if ($kartuKeluarga->rumah_id && $user->rumah_id && $kartuKeluarga->rumah_id !== $user->rumah_id) {
                throw ValidationException::withMessages([
                    'no_kk' => 'Nomor KK tersebut sudah terdaftar di rumah lain.',
                ]);
            }

            $kartuKeluarga->update([
                'rumah_id' => $kartuKeluarga->rumah_id ?? $user->rumah_id,
                'nama_kepala_keluarga' => $statusDalamKk === 'kepala_keluarga'
                    ? $user->name
                    : $kartuKeluarga->nama_kepala_keluarga,
            ]);

            $kartuKeluargaId = $kartuKeluarga->id;
        }

        Warga::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kartu_keluarga_id' => $kartuKeluargaId,
                'nik' => $data['nik'] ?? $user->warga?->nik,
                'nama_lengkap' => $data['nama_lengkap'] ?? $user->name,
                'status_dalam_kk' => $statusDalamKk,
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => 'tatap_muka',
                'diverifikasi_oleh' => auth()->id(),
                'diverifikasi_at' => now(),
            ]
        );
    }
}
