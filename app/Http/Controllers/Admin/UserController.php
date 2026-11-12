<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usersQuery = $this->residentsVisibleTo($request->user())
            ->with(['rumah', 'warga.kartuKeluarga', 'rt:id,name', 'rfidCard.verifiedRt:id,name'])
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

        $rts = $this->availableRts($request->user())->with('rw')->orderBy('rw_id')->orderBy('name')->get();
        $rumahs = Rumah::whereIn('rt_id', $rts->modelKeys())->where('status', 'aktif')->orderBy('kode_rumah')->get();

        return view('admin.warga.index', compact('users', 'rumahs', 'rts'));
    }

    public function edit(Request $request, User $user): View
    {
        if ($user->role_name !== 'warga' || ! $this->residentsVisibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $rts = $this->availableRts($request->user())->with('rw')->orderBy('rw_id')->orderBy('name')->get();
        $rumahs = Rumah::whereIn('rt_id', $rts->modelKeys())->where('status', 'aktif')->orderBy('kode_rumah')->get();

        return view('admin.warga.edit', compact('user', 'rumahs', 'rts'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAktif(), 403);
        if ($user->role_name !== 'warga' || ! $this->residentsVisibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $validated = $request->validate($this->wargaRules($user), $this->wargaMessages());

        $statusDalamKk = $request->boolean('is_kepala_keluarga') ? 'kepala_keluarga' : 'anggota';
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');

        DB::transaction(function () use ($request, $user, $validated, $statusDalamKk) {
            $user = $this->residentsVisibleTo($request->user())->whereRelation('role', 'name', 'warga')->lockForUpdate()->findOrFail($user->id);
            $oldRumahId = $user->rumah_id;
            [$validated['rumah_id'], $validated['rt_id']] = $this->resolvePlacement($validated, $request->user(), $user->rt_id);
            unset($validated['rumah_kode'], $validated['rumah_alamat']);
            $user->fill(collect($validated)->only([
                'name', 'email', 'password', 'phone', 'rumah_id', 'rt_id', 'is_penanggung_jawab_rumah',
            ])->all());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->syncDataWarga($user, $validated, $statusDalamKk);
            $this->syncPenanggungJawabRumah($user, $oldRumahId);
        }, 3);

        return redirect()->route('admin.warga.index')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function create(Request $request): View
    {
        $rts = $this->availableRts($request->user())->with('rw')->orderBy('rw_id')->orderBy('name')->get();
        $rumahs = Rumah::whereIn('rt_id', $rts->modelKeys())->where('status', 'aktif')->orderBy('kode_rumah')->get();

        return view('admin.warga.create', compact('rumahs', 'rts'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAktif(), 403);
        $validated = $request->validate($this->wargaRules(), $this->wargaMessages());

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = Role::firstOrCreate(
            ['name' => 'warga'],
            ['description' => 'Warga']
        )->id;
        $statusDalamKk = $request->boolean('is_kepala_keluarga') ? 'kepala_keluarga' : 'anggota';
        $validated['is_penanggung_jawab_rumah'] = $request->boolean('is_penanggung_jawab_rumah');

        DB::transaction(function () use ($request, $validated, $statusDalamKk) {
            [$validated['rumah_id'], $validated['rt_id']] = $this->resolvePlacement($validated, $request->user());
            unset($validated['rumah_kode'], $validated['rumah_alamat']);
            $user = User::create(collect($validated)->only([
                'name', 'email', 'password', 'phone', 'role_id', 'rumah_id', 'rt_id', 'is_penanggung_jawab_rumah',
            ])->all() + ['status_akun' => 'aktif']);
            $this->syncDataWarga($user, $validated, $statusDalamKk);
            $this->syncPenanggungJawabRumah($user, null);
        }, 3);

        return redirect()->route('admin.warga.index')->with('success', 'Warga baru berhasil ditambahkan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $userName = DB::transaction(function () use ($request, $user) {
            $resident = $this->residentsVisibleTo($request->user())
                ->whereRelation('role', 'name', 'warga')
                ->lockForUpdate()->findOrFail($user->id);
            $name = $resident->name;
            // The User deletion event also guards retained financial history.
            $resident->delete();

            return $name;
        });

        return redirect()->route('admin.warga.index')->with('success', "Warga '{$userName}' berhasil dihapus.");
    }

    private function residentsVisibleTo(User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return User::query();
        }
        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId ? User::inRw($rwId) : User::whereRaw('1 = 0');
        }

        return $actor->rt_id ? User::where('rt_id', $actor->rt_id) : User::whereRaw('1 = 0');
    }

    private function availableRts(User $actor): Builder
    {
        $query = Rt::where('is_active', true)->whereHas('rw', fn ($rw) => $rw->where('is_active', true));
        if ($actor->isGlobalOperator()) {
            return $query;
        }
        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId ? $query->where('rw_id', $rwId) : $query->whereRaw('1 = 0');
        }

        return $actor->rt_id ? $query->whereKey($actor->rt_id) : $query->whereRaw('1 = 0');
    }

    private function resolvePlacement(array $data, User $actor, ?int $currentRtId = null): array
    {
        $kodeRumah = filled($data['rumah_kode'] ?? null) ? strtoupper(trim($data['rumah_kode'])) : null;
        $rumah = null;
        if (! empty($data['rumah_id'])) {
            $rumah = Rumah::billingVisibleTo($actor)->lockForUpdate()->findOrFail($data['rumah_id']);
        } elseif ($kodeRumah) {
            $rumah = Rumah::billingVisibleTo($actor)->where('kode_rumah', $kodeRumah)->lockForUpdate()->first();
            if (! $rumah && Rumah::where('kode_rumah', $kodeRumah)->exists()) {
                throw ValidationException::withMessages(['rumah_kode' => 'Kode rumah tersebut sudah digunakan di wilayah lain.']);
            }
        }

        // A pre-existing house is never silently moved into the selected territory.
        if ($rumah && (! $rumah->rt_id || $rumah->status !== 'aktif')) {
            throw ValidationException::withMessages(['rumah_id' => 'Pilih rumah aktif yang sudah terhubung ke RT. Rumah ini belum dapat digunakan untuk penempatan warga.']);
        }
        if ($rumah && isset($data['rt_id']) && (int) $data['rt_id'] !== (int) $rumah->rt_id) {
            throw ValidationException::withMessages(['rt_id' => 'RT warga harus sama dengan RT rumah yang dipilih.']);
        }

        $rtId = $rumah?->rt_id ?? $data['rt_id'] ?? ($actor->canAccessAllRts() ? $currentRtId : $actor->rt_id);
        $rt = $this->availableRts($actor)->whereKey($rtId)->lockForUpdate()->first();
        if (! $rt) {
            throw ValidationException::withMessages(['rt_id' => 'Pilih RT aktif dalam wilayah kewenangan Anda. Warga aktif wajib terhubung ke RT dengan RW aktif.']);
        }

        if (! $rumah && $kodeRumah) {
            $rumah = Rumah::create(['kode_rumah' => $kodeRumah, 'alamat' => $data['rumah_alamat'] ?? null, 'rt_id' => $rt->id]);
        }
        if ($rumah && filled($data['rumah_alamat'] ?? null) && blank($rumah->alamat)) {
            $rumah->update(['alamat' => $data['rumah_alamat']]);
        }

        return [$rumah?->id, $rt->id];
    }

    private function wargaRules(?User $user = null): array
    {
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($userId ? ','.$userId : '')],
            'password' => [$user ? 'sometimes' : 'required', 'string', 'min:8'],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'rt_id' => ['nullable', 'integer'],
            'rumah_kode' => ['nullable', 'string', 'max:50'],
            'rumah_alamat' => ['nullable', 'string', 'max:500'],
            'no_kk' => ['nullable', 'digits:16'],
            'nik' => ['nullable', 'digits:16', 'unique:wargas,nik'.($user?->warga ? ','.$user->warga->id : '')],
            'nama_lengkap' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,13}$/'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'ganti_kepala_keluarga' => ['nullable', 'boolean', function ($attribute, $value, $fail) {
                if (in_array($value, [true, 1, '1'], true) && ! request()->boolean('is_kepala_keluarga')) {
                    $fail('Konfirmasi penggantian hanya berlaku jika warga ditetapkan sebagai kepala keluarga.');
                }
            }],
            'nama_kepala_keluarga' => ['nullable', 'string', 'max:255'],
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

    private function syncPenanggungJawabRumah(User $user, ?int $oldRumahId = null): void
    {
        $oldRumahId = $oldRumahId ?? $user->getOriginal('rumah_id');
        if ($oldRumahId && (int) $oldRumahId !== (int) $user->rumah_id) {
            Rumah::whereKey($oldRumahId)
                ->where('penanggung_jawab_id', $user->id)
                ->update(['penanggung_jawab_id' => null]);
        }

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
        $actor = auth()->user();
        abort_unless($actor?->isAktif() && $actor->canManageWarga(), 403);
        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();
            abort_unless($rwId && (int) $user->assignedRwId() === (int) $rwId, 404);
        }
        $warga = $user->warga()->first();
        $kartuKeluarga = null;
        $oldValues = [
            'kartu_keluarga_id' => $warga?->kartu_keluarga_id,
            'status_dalam_kk' => $warga?->status_dalam_kk,
        ];
        $noKk = $data['no_kk'] ?? $warga?->kartuKeluarga?->no_kk;

        if (filled($noKk)) {
            $kartuKeluarga = KartuKeluarga::firstOrCreate(
                ['no_kk' => $noKk],
                [
                    'rumah_id' => $user->rumah_id,
                    'nama_kepala_keluarga' => $statusDalamKk === 'kepala_keluarga'
                        ? $user->name
                        : ($data['nama_kepala_keluarga'] ?? ''),
                ]
            );
            // Serialize all membership/head changes for this KK, including creation.
            $kartuKeluarga = KartuKeluarga::whereKey($kartuKeluarga->id)->lockForUpdate()->firstOrFail();
            if (blank($kartuKeluarga->nama_kepala_keluarga) && $statusDalamKk !== 'kepala_keluarga') {
                throw ValidationException::withMessages(['nama_kepala_keluarga' => 'Isi nama kepala keluarga yang sebenarnya untuk KK baru. Nama anggota tidak digunakan sebagai kepala keluarga.']);
            }

            if ($kartuKeluarga->rumah_id && (int) $kartuKeluarga->rumah_id !== (int) $user->rumah_id) {
                throw ValidationException::withMessages([
                    'no_kk' => 'Nomor KK tersebut sudah terdaftar di rumah lain.',
                ]);
            }

            if (! $kartuKeluarga->rumah_id && $kartuKeluarga->wargas()->where('user_id', '!=', $user->id)
                ->whereHas('user', function ($query) use ($user) {
                    $query->where(function ($scope) use ($user) {
                        $scope->whereNotNull('rumah_id');
                        if ($user->rumah_id) {
                            $scope->where('rumah_id', '!=', $user->rumah_id);
                        }
                    })->orWhere(function ($scope) use ($user) {
                        $scope->whereNotNull('rt_id');
                        if ($user->rt_id) {
                            $scope->where('rt_id', '!=', $user->rt_id);
                        }
                    });
                })->exists()) {
                throw ValidationException::withMessages(['no_kk' => 'KK tersebut memiliki anggota di rumah atau wilayah lain. Periksa penempatan keluarga terlebih dahulu.']);
            }

            $oldValues['nama_kepala_keluarga'] = $kartuKeluarga->nama_kepala_keluarga;
            $kepalaLain = $kartuKeluarga->wargas()->where('user_id', '!=', $user->id)
                ->where('status_dalam_kk', 'kepala_keluarga')->lockForUpdate()->get();
            $oldValues['kepala_lain_ids'] = $kepalaLain->modelKeys();
            if ($statusDalamKk === 'kepala_keluarga' && $kepalaLain->isNotEmpty()) {
                if (! ($data['ganti_kepala_keluarga'] ?? false)) {
                    throw ValidationException::withMessages(['is_kepala_keluarga' => 'KK ini sudah memiliki kepala keluarga. Centang konfirmasi penggantian jika hasil pemeriksaan pengurus menetapkan kepala yang baru.']);
                }
                Warga::whereKey($kepalaLain->modelKeys())->update(['status_dalam_kk' => 'anggota']);
            }

            $kartuKeluarga->update([
                'rumah_id' => $kartuKeluarga->rumah_id ?? $user->rumah_id,
                'nama_kepala_keluarga' => $statusDalamKk === 'kepala_keluarga'
                    ? $user->name
                    : $kartuKeluarga->nama_kepala_keluarga,
            ]);

        }

        $warga = Warga::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kartu_keluarga_id' => $kartuKeluarga?->id ?? $warga?->kartu_keluarga_id,
                'nik' => $data['nik'] ?? $user->warga?->nik,
                'nama_lengkap' => $data['nama_lengkap'] ?? $user->name,
                'status_dalam_kk' => $statusDalamKk,
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => 'tatap_muka',
                'diverifikasi_oleh' => auth()->id(),
                'diverifikasi_at' => now(),
            ]
        );

        $newValues = [
            'kartu_keluarga_id' => $warga->kartu_keluarga_id, 'status_dalam_kk' => $warga->status_dalam_kk,
            'nama_kepala_keluarga' => $kartuKeluarga?->nama_kepala_keluarga,
        ];
        if ($oldValues !== $newValues) {
            AuditLog::create([
                'user_id' => auth()->id(), 'auditable_type' => Warga::class, 'auditable_id' => $warga->id,
                'event' => 'keluarga_diperbarui', 'old_values' => $oldValues, 'new_values' => $newValues,
                'notes' => ($data['ganti_kepala_keluarga'] ?? false) ? 'Pengurus mengonfirmasi penggantian kepala keluarga.' : 'Penetapan data keluarga oleh pengurus.',
                'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
