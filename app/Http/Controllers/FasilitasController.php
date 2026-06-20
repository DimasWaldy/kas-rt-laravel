<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FasilitasController extends Controller
{
    public function index(Request $request)
    {
        $query = Fasilitas::with(['rw', 'rt'])
            ->where('rw_id', $this->resolveRwId($request->user()))
            ->when(! $request->user()->canAccessAllRts(), function (Builder $query) use ($request) {
                $query->where(function (Builder $query) use ($request) {
                    $query->whereNull('rt_id')
                        ->orWhere('rt_id', $request->user()->rt_id);
                });
            })
            ->latest();

        $filters = [
            'kategori' => $request->string('kategori')->toString(),
            'kondisi' => $request->string('kondisi')->toString(),
        ];

        if (in_array($filters['kategori'], $this->categories(), true)) {
            $query->where('kategori', $filters['kategori']);
        }

        if (in_array($filters['kondisi'], $this->conditions(), true)) {
            $query->where('kondisi', $filters['kondisi']);
        }

        return view('fasilitas.index', [
            'fasilitas' => $query->paginate(12)->withQueryString(),
            'filters' => $filters,
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('manage-fasilitas'), 403);

        return view('fasilitas.create', [
            'rts' => $this->availableRts($user),
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);

        $validated = $this->validateFasilitas($request);
        [$rwId, $rtId] = $this->resolveFacilityScope($request->user(), $validated['rt_id'] ?? null);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('fasilitas', 'local');
        }

        $validated['rw_id'] = $rwId;
        $validated['rt_id'] = $rtId;
        $validated['is_active'] = $request->boolean('is_active', true);

        $fasilitas = Fasilitas::create($validated);

        return redirect()->route('fasilitas.show', $fasilitas)
            ->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function show(Fasilitas $fasilitas)
    {
        $this->authorizeVisible($fasilitas, Auth::user());
        $fasilitas->load([
            'rw',
            'rt',
            'pengaduanFasilitas' => fn($query) => $query->with('pelapor')->latest(),
        ]);

        return view('fasilitas.show', compact('fasilitas'));
    }

    public function foto(Fasilitas $fasilitas)
    {
        abort_unless(Auth::check(), 403);
        $this->authorizeVisible($fasilitas, Auth::user());
        abort_unless($fasilitas->foto && Storage::disk('local')->exists($fasilitas->foto), 404);

        return Storage::disk('local')->response($fasilitas->foto);
    }

    public function edit(Fasilitas $fasilitas)
    {
        $this->authorizeManage($fasilitas, Auth::user());

        return view('fasilitas.edit', [
            'fasilitas' => $fasilitas,
            'rts' => $this->availableRts(Auth::user()),
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
        ]);
    }

    public function update(Request $request, Fasilitas $fasilitas)
    {
        $this->authorizeManage($fasilitas, $request->user());

        $validated = $this->validateFasilitas($request);
        [$rwId, $rtId] = $this->resolveFacilityScope($request->user(), $validated['rt_id'] ?? null);

        if ($request->hasFile('foto')) {
            if ($fasilitas->foto) {
                Storage::disk('local')->delete($fasilitas->foto);
            }

            $validated['foto'] = $request->file('foto')->store('fasilitas', 'local');
        }

        $validated['rw_id'] = $rwId;
        $validated['rt_id'] = $rtId;
        $validated['is_active'] = $request->boolean('is_active');

        $fasilitas->update($validated);

        return redirect()->route('fasilitas.show', $fasilitas)
            ->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Fasilitas $fasilitas)
    {
        $this->authorizeManage($fasilitas, Auth::user());

        $hasActiveComplaint = $fasilitas->pengaduanFasilitas()
            ->whereIn('status', ['dilaporkan', 'ditindaklanjuti'])
            ->exists();

        if ($hasActiveComplaint) {
            return back()->with('error', 'Fasilitas tidak bisa dihapus karena masih ada pengaduan aktif.');
        }

        if ($fasilitas->foto) {
            Storage::disk('local')->delete($fasilitas->foto);
        }

        $fasilitas->delete();

        return redirect()->route('fasilitas.index')
            ->with('success', 'Fasilitas berhasil dihapus.');
    }

    private function validateFasilitas(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in($this->categories())],
            'rt_id' => ['nullable', 'integer', 'exists:rts,id'],
            'lokasi_blok' => ['nullable', 'string', 'max:100'],
            'lokasi_deskripsi' => ['nullable', 'string', 'max:500'],
            'kondisi' => ['required', Rule::in($this->conditions())],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeVisible(Fasilitas $fasilitas, User $user): void
    {
        abort_unless($user->hasPermission('view-fasilitas'), 403);

        if ($user->isGlobalOperator()) {
            return;
        }

        abort_unless($fasilitas->rw_id === $this->resolveRwId($user), 403);

        if (! $user->canAccessAllRts() && $fasilitas->rt_id) {
            abort_unless($fasilitas->rt_id === $user->rt_id, 403);
        }
    }

    private function authorizeManage(Fasilitas $fasilitas, User $user): void
    {
        abort_unless($user->hasPermission('manage-fasilitas'), 403);
        $this->authorizeVisible($fasilitas, $user);

        if (! $user->canAccessAllRts() && $fasilitas->rt_id) {
            abort_unless($fasilitas->rt_id === $user->rt_id, 403);
        }
    }

    private function resolveFacilityScope(User $user, mixed $requestedRtId): array
    {
        $rwId = $this->resolveRwId($user);

        if (! $user->canAccessAllRts()) {
            abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

            return [$rwId, (int) $user->rt_id];
        }

        if (! filled($requestedRtId)) {
            return [$rwId, null];
        }

        $rt = Rt::whereKey($requestedRtId)
            ->where('rw_id', $rwId)
            ->where('is_active', true)
            ->firstOrFail();

        return [$rwId, $rt->id];
    }

    private function availableRts(User $user)
    {
        if (! $user->canAccessAllRts()) {
            return collect();
        }

        return Rt::where('rw_id', $this->resolveRwId($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
            'cctv',
            'pos_satpam',
            'lapangan',
            'taman',
            'jalan',
            'drainase',
            'penerangan',
            'lainnya',
        ];
    }

    private function conditions(): array
    {
        return [
            'baik',
            'perlu_perhatian',
            'rusak',
        ];
    }
}
