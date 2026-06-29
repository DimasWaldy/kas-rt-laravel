<?php

namespace App\Http\Controllers;

use App\Models\Balita;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\WhoGrowthStandard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosyanduController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $baseQuery = $this->visibleBalitaQuery($user);
        $query = (clone $baseQuery)->with(['rt', 'orangTua'])
            ->with('pemeriksaanTerakhir');

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('no_kk', 'like', "%{$search}%")
                    ->orWhereHas('orangTua', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('rt_id') && $this->canAccessAllRts($user)) {
            $query->where('rt_id', $request->integer('rt_id'));
        }

        if ($request->has('is_active') && in_array($request->string('is_active')->toString(), ['0', '1'], true)) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return view('posyandu.index', [
            'balitas' => $query->latest('tanggal_lahir')->paginate(15)->withQueryString(),
            'rts' => $this->availableRts($user),
            'filters' => [
                'search' => $search,
                'rt_id' => $request->integer('rt_id') ?: null,
                'is_active' => $request->string('is_active')->toString(),
            ],
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'aktif' => (clone $baseQuery)->where('is_active', true)->count(),
                'perlu_perhatian' => (clone $baseQuery)
                    ->whereHas('pemeriksaanTerakhir', fn (Builder $query) => $query
                        ->whereIn('status_bb_u', ['berat_sangat_kurang', 'berat_kurang']))
                    ->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-posyandu'), 403);

        return view('posyandu.create', $this->formOptions($request->user()));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('manage-posyandu'), 403);

        $validated = $this->validateBalita($request, creating: true);
        [$rwId, $rtId] = $this->resolveBalitaScope($user, $validated['rt_id'] ?? null);
        $this->validateFamilyScope($validated, $rtId);

        $validated['rw_id'] = $rwId;
        $validated['rt_id'] = $rtId;
        $validated['is_active'] = $request->boolean('is_active', true);

        $balita = Balita::create($validated);

        return redirect()->route('posyandu.show', $balita)
            ->with('success', 'Data balita berhasil ditambahkan.');
    }

    public function show(Request $request, Balita $balita)
    {
        $this->authorizeVisible($request->user(), $balita);
        $balita->load(['rw', 'rt', 'rumah', 'orangTua', 'pemeriksaans.petugas']);

        $kmsStandards = WhoGrowthStandard::query()
            ->weightForAge()
            ->forGender($balita->jenis_kelamin)
            ->orderBy('usia_bulan')
            ->get();

        return view('posyandu.show', compact('balita', 'kmsStandards'));
    }

    public function edit(Request $request, Balita $balita)
    {
        $this->authorizeManage($request->user(), $balita);

        return view('posyandu.edit', [
            'balita' => $balita,
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(Request $request, Balita $balita)
    {
        $user = $request->user();
        $this->authorizeManage($user, $balita);

        $validated = $this->validateBalita($request, $balita);
        [$rwId, $rtId] = $this->resolveBalitaScope($user, $validated['rt_id'] ?? $balita->rt_id);
        $this->validateFamilyScope($validated, $rtId);

        $validated['rw_id'] = $rwId;
        $validated['rt_id'] = $rtId;
        $validated['is_active'] = $request->boolean('is_active');
        $balita->update($validated);

        return redirect()->route('posyandu.show', $balita)
            ->with('success', 'Data balita berhasil diperbarui.');
    }

    public function toggleActive(Request $request, Balita $balita)
    {
        $this->authorizeManage($request->user(), $balita);
        $balita->update(['is_active' => ! $balita->is_active]);

        return back()->with('success', $balita->is_active
            ? 'Data balita diaktifkan kembali.'
            : 'Data balita dinonaktifkan.');
    }

    private function validateBalita(Request $request, ?Balita $balita = null, bool $creating = false): array
    {
        $validated = $request->validate([
            'rt_id' => ['nullable', 'integer', 'exists:rts,id'],
            'rumah_id' => ['nullable', 'integer', 'exists:rumahs,id'],
            'orang_tua_id' => ['nullable', 'integer', 'exists:users,id'],
            'nik' => ['nullable', 'digits:16', Rule::unique('balitas', 'nik')->ignore($balita?->id)],
            'no_kk' => ['nullable', 'digits:16'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Balita::JENIS_KELAMIN))],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'berat_lahir_kg' => ['nullable', 'numeric', 'between:0.5,10'],
            'panjang_lahir_cm' => ['nullable', 'numeric', 'between:20,80'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($creating && CarbonImmutable::parse($validated['tanggal_lahir'])->lt(today()->subYears(5))) {
            throw ValidationException::withMessages([
                'tanggal_lahir' => 'Pendaftaran baru hanya untuk anak berusia maksimal 5 tahun.',
            ]);
        }

        return $validated;
    }

    private function validateFamilyScope(array $validated, int $rtId): void
    {
        if (! empty($validated['orang_tua_id'])) {
            $validParent = User::whereKey($validated['orang_tua_id'])
                ->where('rt_id', $rtId)
                ->whereHas('role', fn (Builder $query) => $query->where('name', 'warga'))
                ->exists();
            if (! $validParent) {
                throw ValidationException::withMessages([
                    'orang_tua_id' => 'Orang tua atau wali harus berasal dari RT yang sama.',
                ]);
            }
        }

        if (! empty($validated['rumah_id'])) {
            $validRumah = Rumah::whereKey($validated['rumah_id'])
                ->where('rt_id', $rtId)
                ->exists();
            if (! $validRumah) {
                throw ValidationException::withMessages([
                    'rumah_id' => 'Rumah harus berada di RT yang sama dengan balita.',
                ]);
            }
        }
    }

    private function visibleBalitaQuery(User $user): Builder
    {
        $query = Balita::query()->where('rw_id', $this->resolveRwId($user));

        if ($user->role_name === 'warga') {
            return $query->where('orang_tua_id', $user->id);
        }

        if ($this->canAccessAllRts($user)) {
            return $query;
        }

        abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

        return $query->where('rt_id', $user->rt_id);
    }

    private function authorizeVisible(User $user, Balita $balita): void
    {
        abort_unless(
            $this->visibleBalitaQuery($user)->whereKey($balita->id)->exists(),
            403
        );
    }

    private function authorizeManage(User $user, Balita $balita): void
    {
        abort_unless($user->hasPermission('manage-posyandu'), 403);
        $this->authorizeVisible($user, $balita);
    }

    private function resolveBalitaScope(User $user, mixed $requestedRtId): array
    {
        $rwId = $this->resolveRwId($user);

        if (! $this->canAccessAllRts($user)) {
            abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

            return [$rwId, (int) $user->rt_id];
        }

        if (! filled($requestedRtId)) {
            throw ValidationException::withMessages([
                'rt_id' => 'RT balita wajib dipilih.',
            ]);
        }

        $rt = Rt::whereKey($requestedRtId)
            ->where('rw_id', $rwId)
            ->where('is_active', true)
            ->firstOrFail();

        return [$rwId, $rt->id];
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($this->canAccessAllRts($user) || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function canAccessAllRts(User $user): bool
    {
        return $user->canAccessAllRts() || $user->role_name === 'petugas_posyandu';
    }

    private function availableRts(User $user)
    {
        if (! $this->canAccessAllRts($user)) {
            return collect();
        }

        return Rt::where('rw_id', $this->resolveRwId($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function formOptions(User $user): array
    {
        $rwId = $this->resolveRwId($user);
        $rtIds = $this->canAccessAllRts($user)
            ? Rt::where('rw_id', $rwId)->pluck('id')
            : collect([$user->rt_id]);

        return [
            'rts' => $this->availableRts($user),
            'orangTuas' => User::with('rt')
                ->whereIn('rt_id', $rtIds)
                ->whereHas('role', fn (Builder $query) => $query->where('name', 'warga'))
                ->orderBy('name')
                ->get(),
            'rumahs' => Rumah::whereIn('rt_id', $rtIds)->orderBy('kode_rumah')->get(),
        ];
    }
}
