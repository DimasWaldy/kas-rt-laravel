<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AsetController extends Controller
{
    public function index(Request $request)
    {
        $scope = $this->resolveScope($request);
        $this->authorizeViewScope($scope, $request->user());

        $query = Aset::with(['rt', 'rw'])
            ->where('scope', $scope)
            ->latest();

        if ($scope === 'rw') {
            $query->where('rw_id', $this->resolveRwId($request->user()));
        } else {
            $query->where('rt_id', $this->resolveRtId($request->user()));
        }

        $filters = [
            'scope' => $scope,
            'kategori' => $request->string('kategori')->toString(),
            'kondisi' => $request->string('kondisi')->toString(),
            'is_active' => $request->string('is_active')->toString(),
        ];

        if (in_array($filters['kategori'], $this->categories(), true)) {
            $query->where('kategori', $filters['kategori']);
        }

        if (in_array($filters['kondisi'], $this->conditions(), true)) {
            $query->where('kondisi', $filters['kondisi']);
        }

        if (in_array($filters['is_active'], ['0', '1'], true)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return view('aset.index', [
            'asets' => $query->paginate(12)->withQueryString(),
            'filters' => $filters,
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $scope = $this->resolveScope(request());
        $this->authorizeManageScope($scope, $user);

        return view('aset.create', [
            'scope' => $scope,
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAset($request);
        $scope = $this->resolveScope($request);
        $this->authorizeManageScope($scope, $request->user());
        $validated['scope'] = $scope;
        [$validated['rw_id'], $validated['rt_id']] = $this->resolveOwnerIds($scope, $request->user());
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('aset', 'local');
        }

        $aset = Aset::create($validated);

        return redirect()->route('aset.show', $aset)
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Aset $aset)
    {
        $this->authorizeVisible($aset, Auth::user());
        $aset->load(['rt', 'rw']);

        $peminjamanAktif = $aset->peminjamanAset()
            ->with('pemohon')
            ->whereIn('status', ['disetujui', 'dipinjam'])
            ->orderBy('tanggal_mulai')
            ->get();

        $riwayat = $aset->peminjamanAset()
            ->with('pemohon')
            ->latest()
            ->limit(10)
            ->get();

        return view('aset.show', compact('aset', 'peminjamanAktif', 'riwayat'));
    }

    public function foto(Aset $aset)
    {
        abort_unless(Auth::check(), 403);
        $this->authorizeVisible($aset, Auth::user());
        abort_unless($aset->foto && Storage::disk('local')->exists($aset->foto), 404);

        return Storage::disk('local')->response($aset->foto);
    }

    public function edit(Aset $aset)
    {
        $this->authorizeManage($aset, Auth::user());

        return view('aset.edit', [
            'aset' => $aset,
            'scope' => $aset->scope,
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function update(Request $request, Aset $aset)
    {
        $this->authorizeManage($aset, $request->user());

        $validated = $this->validateAset($request);
        $validated['scope'] = $aset->scope;
        [$validated['rw_id'], $validated['rt_id']] = $this->resolveOwnerIds($aset->scope, $request->user());
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('foto')) {
            if ($aset->foto) {
                Storage::disk('local')->delete($aset->foto);
            }

            $validated['foto'] = $request->file('foto')->store('aset', 'local');
        }

        $aset->update($validated);

        return redirect()->route('aset.show', $aset)
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Aset $aset)
    {
        $this->authorizeManage($aset, Auth::user());

        $hasActiveLoan = $aset->peminjamanAset()
            ->whereIn('status', ['disetujui', 'dipinjam'])
            ->exists();

        if ($hasActiveLoan) {
            return back()->with('error', 'Aset tidak bisa dihapus karena masih ada peminjaman aktif.');
        }

        if ($aset->foto) {
            Storage::disk('local')->delete($aset->foto);
        }

        $aset->delete();

        return redirect()->route('aset.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    private function validateAset(Request $request): array
    {
        return $request->validate([
            'scope' => ['nullable', Rule::in(['rt', 'rw'])],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in($this->categories())],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'jumlah_total' => ['required', 'integer', 'min:1', 'max:100'],
            'kondisi' => ['required', Rule::in($this->conditions())],
            'nilai_perkiraan' => ['nullable', 'integer', 'min:0'],
            'tanggal_pengadaan' => ['nullable', 'date', 'before_or_equal:today'],
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);
    }

    private function authorizeManage(Aset $aset, User $user): void
    {
        $this->authorizeManageScope($aset->scope, $user);
        $this->authorizeVisible($aset, $user);
    }

    private function authorizeVisible(Aset $aset, User $user): void
    {
        $this->authorizeViewScope($aset->scope, $user);

        if ($user->isGlobalOperator()) {
            return;
        }

        if ($aset->isRwAsset()) {
            abort_unless($aset->rw_id === $this->resolveRwId($user), 403);
            return;
        }

        abort_unless($aset->rt_id === $this->resolveRtId($user), 403);
    }

    private function authorizeViewScope(string $scope, User $user): void
    {
        abort_unless(
            $scope === 'rw'
                ? $user->hasPermission('view-aset-rw')
                : $user->hasPermission('view-aset'),
            403
        );
    }

    private function authorizeManageScope(string $scope, User $user): void
    {
        abort_unless(
            $scope === 'rw'
                ? $user->hasPermission('manage-aset-rw')
                : $user->hasPermission('manage-aset'),
            403
        );
    }

    private function resolveOwnerIds(string $scope, User $user): array
    {
        if ($scope === 'rw') {
            return [$this->resolveRwId($user), null];
        }

        $rtId = $this->resolveRtId($user);
        $rwId = $this->resolveRwId($user);

        return [$rwId, $rtId];
    }

    private function resolveScope(Request $request): string
    {
        $scope = $request->route('scope') ?: $request->string('scope')->toString();

        return in_array($scope, ['rt', 'rw'], true) ? $scope : 'rt';
    }

    private function resolveRtId(User $user): int
    {
        abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

        return (int) $user->rt_id;
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($user->isRwOfficial() || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke RW.');

        return (int) $rwId;
    }

    private function categories(): array
    {
        return [
            'furniture',
            'elektronik',
            'tenda_dan_terpal',
            'kebersihan',
            'olahraga',
            'gedung',
            'lapangan',
            'panggung',
            'lainnya',
        ];
    }

    private function conditions(): array
    {
        return [
            'baik',
            'rusak_ringan',
            'rusak_berat',
        ];
    }
}
