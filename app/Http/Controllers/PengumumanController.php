<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $kategori = $request->string('kategori')->toString();
        $prioritas = $request->string('prioritas')->toString();
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $query = Pengumuman::visibleTo($user)
            ->with(['rt:id,name', 'rw:id,name', 'creator:id,name'])
            ->ordered();

        if (array_key_exists($kategori, Pengumuman::CATEGORIES)) {
            $query->where('kategori', $kategori);
        }

        if (array_key_exists($prioritas, Pengumuman::PRIORITIES)) {
            $query->where('prioritas', $prioritas);
        }

        if ($status === 'aktif') {
            $query->activeWindow();
        } elseif ($status === 'arsip') {
            $query->where(function ($q) {
                $q->where('is_published', false)
                    ->orWhere(function ($expired) {
                        $expired->whereNotNull('tanggal_berakhir')
                            ->where('tanggal_berakhir', '<', now());
                    });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('isi', 'like', "%{$search}%");
            });
        }

        $baseScope = Pengumuman::visibleTo($user);
        $counts = [
            'aktif' => (clone $baseScope)->activeWindow()->count(),
            'darurat' => (clone $baseScope)->activeWindow()->whereIn('prioritas', ['darurat', 'penting'])->count(),
            'disematkan' => (clone $baseScope)->activeWindow()->where('is_pinned', true)->count(),
        ];

        $canManage = $user->hasPermission('manage-pengumuman');
        $isRwOfficial = $user->isRwOfficial() || $user->isGlobalOperator();

        return view('pengumuman.index', [
            'pengumumans' => $query->paginate(10)->withQueryString(),
            'counts' => $counts,
            'kategori' => $kategori,
            'prioritas' => $prioritas,
            'status' => $status,
            'search' => $search,
            'categories' => Pengumuman::CATEGORIES,
            'priorities' => Pengumuman::PRIORITIES,
            'canManage' => $canManage,
            'isRwOfficial' => $isRwOfficial,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('manage-pengumuman'), 403);

        $isRwOfficial = $user->isRwOfficial() || $user->isGlobalOperator();
        $rwId = $this->resolveRwId($user);

        if (! $isRwOfficial) {
            abort_unless($user->rt_id, 403, 'Akun pengurus RT belum terhubung ke wilayah RT.');
        }

        $rts = $isRwOfficial
            ? Rt::where('rw_id', $rwId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('pengumuman.create', [
            'rts' => $rts,
            'isRwOfficial' => $isRwOfficial,
            'categories' => Pengumuman::CATEGORIES,
            'priorities' => Pengumuman::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('manage-pengumuman'), 403);

        $validated = $this->validatePengumuman($request);
        [$rwId, $rtId] = $this->resolveTargetScope($user, $validated['rt_id'] ?? null);

        $pengumuman = Pengumuman::create([
            'rw_id' => $rwId,
            'rt_id' => $rtId,
            'created_by' => Auth::id(),
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kategori' => $validated['kategori'],
            'prioritas' => $validated['prioritas'],
            'is_pinned' => $request->boolean('is_pinned'),
            'is_public' => $request->boolean('is_public', true),
            'is_published' => $request->boolean('is_published', true),
            'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
            'tanggal_berakhir' => $validated['tanggal_berakhir'] ?? null,
        ]);

        return redirect()->route('pengumuman.index')
            ->with('success', $pengumuman->is_published
                ? 'Pengumuman berhasil diterbitkan ke papan pengumuman.'
                : 'Pengumuman berhasil disimpan sebagai draf.');
    }

    public function edit(Request $request, Pengumuman $pengumuman): View
    {
        $user = $request->user();
        $this->authorizeManage($pengumuman, $user);

        $isRwOfficial = $user->isRwOfficial() || $user->isGlobalOperator();
        $rts = $isRwOfficial
            ? Rt::where('rw_id', $this->resolveRwId($user))->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('pengumuman.edit', [
            'pengumuman' => $pengumuman,
            'rts' => $rts,
            'isRwOfficial' => $isRwOfficial,
            'categories' => Pengumuman::CATEGORIES,
            'priorities' => Pengumuman::PRIORITIES,
        ]);
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeManage($pengumuman, $user);

        $validated = $this->validatePengumuman($request);
        [$rwId, $rtId] = $this->resolveTargetScope($user, $validated['rt_id'] ?? null);

        $pengumuman->update([
            'rw_id' => $rwId,
            'rt_id' => $rtId,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kategori' => $validated['kategori'],
            'prioritas' => $validated['prioritas'],
            'is_pinned' => $request->boolean('is_pinned'),
            'is_public' => $request->boolean('is_public'),
            'is_published' => $request->boolean('is_published'),
            'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
            'tanggal_berakhir' => $validated['tanggal_berakhir'] ?? null,
        ]);

        return redirect()->route('pengumuman.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function togglePin(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $this->authorizeManage($pengumuman, $request->user());

        $pengumuman->update([
            'is_pinned' => ! $pengumuman->is_pinned,
        ]);

        return back()->with('success', $pengumuman->is_pinned
            ? 'Pengumuman disematkan di bagian atas.'
            : 'Semat pengumuman dilepas.');
    }

    public function togglePublish(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $this->authorizeManage($pengumuman, $request->user());

        $pengumuman->update([
            'is_published' => ! $pengumuman->is_published,
        ]);

        return back()->with('success', $pengumuman->is_published
            ? 'Pengumuman kembali diterbitkan.'
            : 'Pengumuman diarsipkan.');
    }

    public function destroy(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $this->authorizeManage($pengumuman, $request->user());

        $pengumuman->delete();

        return redirect()->route('pengumuman.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePengumuman(Request $request): array
    {
        $endRules = ['nullable', 'date'];
        if ($request->filled('tanggal_mulai')) {
            $endRules[] = 'after_or_equal:tanggal_mulai';
        }

        return $request->validate([
            'judul' => ['required', 'string', 'min:3', 'max:150'],
            'isi' => ['required', 'string', 'min:10', 'max:3000'],
            'kategori' => ['required', Rule::in(array_keys(Pengumuman::CATEGORIES))],
            'prioritas' => ['required', Rule::in(array_keys(Pengumuman::PRIORITIES))],
            'rt_id' => ['nullable', 'integer', 'exists:rts,id'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_berakhir' => $endRules,
        ]);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->assignedRwId();

        if (! $rwId && $user->isGlobalOperator()) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function resolveTargetScope(User $user, mixed $requestedRtId): array
    {
        $rwId = $this->resolveRwId($user);

        if (! ($user->isRwOfficial() || $user->isGlobalOperator())) {
            abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

            return [$rwId, (int) $user->rt_id];
        }

        if ($requestedRtId === null || $requestedRtId === '') {
            return [$rwId, null];
        }

        $rt = Rt::where('rw_id', $rwId)
            ->where('is_active', true)
            ->find($requestedRtId);

        if (! $rt) {
            throw ValidationException::withMessages([
                'rt_id' => 'RT tujuan harus aktif dan berada pada wilayah RW Anda.',
            ]);
        }

        return [$rwId, (int) $rt->id];
    }

    private function authorizeManage(Pengumuman $pengumuman, User $user): void
    {
        abort_unless($user->hasPermission('manage-pengumuman'), 403);
        abort_unless($pengumuman->rw_id === $this->resolveRwId($user), 403);

        if ($user->isRwOfficial() || $user->isGlobalOperator()) {
            return;
        }

        abort_unless(
            $user->rt_id && $pengumuman->rt_id === $user->rt_id,
            403,
            'Pengurus RT hanya dapat mengelola pengumuman milik RT sendiri.'
        );
    }
}
