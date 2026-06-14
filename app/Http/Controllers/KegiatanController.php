<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Kegiatan::visibleTo($request->user())
            ->with(['rw', 'rt', 'creator'])
            ->withCount('hadirs')
            ->orderByDesc('tanggal_mulai');

        $status = $request->string('status')->toString();
        if (in_array($status, $this->statuses(), true)) {
            $this->applyStatusFilter($query, $status);
        }

        return view('kegiatan.index', [
            'kegiatans' => $query->paginate(12)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('manage-kegiatan'), 403);
        $rwId = $this->resolveRwId($user);
        $isRwOfficial = $user->isRwOfficial() || $user->isGlobalOperator();
        $rts = $isRwOfficial
            ? Rt::where('rw_id', $rwId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('kegiatan.create', compact('rts', 'isRwOfficial'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-kegiatan'), 403);
        $validated = $this->validateKegiatan($request, true);
        [$rwId, $rtId] = $this->resolveActivityScope($request->user(), $validated['rt_id'] ?? null);

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('kegiatan', 'local')
            : null;

        unset($validated['foto'], $validated['foto_dokumentasi'], $validated['rt_id']);

        $kegiatan = Kegiatan::create([
            ...$validated,
            'rw_id' => $rwId,
            'rt_id' => $rtId,
            'created_by' => Auth::id(),
            'foto' => $fotoPath,
            'estimasi_biaya' => $validated['estimasi_biaya'] ?? 0,
            'realisasi_biaya' => $validated['realisasi_biaya'] ?? 0,
            'status' => 'akan_datang',
        ]);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function show(Kegiatan $kegiatan)
    {
        $this->authorizeVisible($kegiatan, Auth::user());
        $kegiatan->load(['rw', 'rt', 'creator', 'hadirs.user']);

        $sudahHadir = $kegiatan->hadirs()
            ->where('user_id', Auth::id())
            ->exists();

        return view('kegiatan.show', compact('kegiatan', 'sudahHadir'));
    }

    public function foto(Kegiatan $kegiatan)
    {
        abort_unless(Auth::check(), 403);
        $this->authorizeVisible($kegiatan, Auth::user());
        abort_unless($kegiatan->foto && Storage::disk('local')->exists($kegiatan->foto), 404);

        return Storage::disk('local')->response($kegiatan->foto);
    }

    public function dokumentasi(Kegiatan $kegiatan)
    {
        abort_unless(Auth::check(), 403);
        $this->authorizeVisible($kegiatan, Auth::user());
        abort_unless(
            $kegiatan->foto_dokumentasi && Storage::disk('local')->exists($kegiatan->foto_dokumentasi),
            404
        );

        return Storage::disk('local')->response($kegiatan->foto_dokumentasi);
    }

    public function konfirmasiHadir(Request $request, Kegiatan $kegiatan)
    {
        abort_unless($request->user()->hasPermission('view-kegiatan'), 403);
        $this->authorizeVisible($kegiatan, $request->user());

        if (in_array($kegiatan->effective_status, ['dibatalkan', 'selesai'], true)) {
            return back()->with('error', 'Kegiatan yang sudah selesai atau dibatalkan tidak dapat dikonfirmasi.');
        }

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $kegiatan->hadirs()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'konfirmasi_at' => now(),
                'catatan' => $validated['catatan'] ?? null,
            ]
        );

        return back()->with('success', 'Konfirmasi kehadiran berhasil disimpan.');
    }

    public function edit(Kegiatan $kegiatan)
    {
        $this->authorizeManage($kegiatan, Auth::user());

        $user = Auth::user();
        $isRwOfficial = $user->isRwOfficial() || $user->isGlobalOperator();
        $rts = $isRwOfficial
            ? Rt::where('rw_id', $this->resolveRwId($user))->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('kegiatan.edit', compact('kegiatan', 'rts', 'isRwOfficial'));
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $this->authorizeManage($kegiatan, $request->user());
        $validated = $this->validateKegiatan($request);
        [$rwId, $rtId] = $this->resolveActivityScope($request->user(), $validated['rt_id'] ?? null);

        if ($request->hasFile('foto_dokumentasi') && now()->isBefore($validated['tanggal_mulai'])) {
            throw ValidationException::withMessages([
                'foto_dokumentasi' => 'Dokumentasi baru dapat ditambahkan setelah kegiatan dimulai.',
            ]);
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            if ($kegiatan->foto) {
                Storage::disk('local')->delete($kegiatan->foto);
            }

            $fotoPath = $request->file('foto')->store('kegiatan', 'local');
        }

        $dokumentasiPath = null;
        if ($request->hasFile('foto_dokumentasi')) {
            if ($kegiatan->foto_dokumentasi) {
                Storage::disk('local')->delete($kegiatan->foto_dokumentasi);
            }

            $dokumentasiPath = $request->file('foto_dokumentasi')
                ->store('kegiatan/dokumentasi', 'local');
        }

        unset($validated['foto'], $validated['foto_dokumentasi'], $validated['rt_id']);
        if ($fotoPath) {
            $validated['foto'] = $fotoPath;
        }
        if ($dokumentasiPath) {
            $validated['foto_dokumentasi'] = $dokumentasiPath;
        }
        $validated['rw_id'] = $rwId;
        $validated['rt_id'] = $rtId;
        $validated['estimasi_biaya'] = $validated['estimasi_biaya'] ?? 0;
        $validated['realisasi_biaya'] = $validated['realisasi_biaya'] ?? 0;
        $kegiatan->update($validated);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        $this->authorizeManage($kegiatan, Auth::user());

        if ($kegiatan->foto) {
            Storage::disk('local')->delete($kegiatan->foto);
        }

        if ($kegiatan->foto_dokumentasi) {
            Storage::disk('local')->delete($kegiatan->foto_dokumentasi);
        }

        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function batalkan(Request $request, Kegiatan $kegiatan)
    {
        abort_unless($request->user()->hasPermission('manage-kegiatan'), 403);
        $this->authorizeManage($kegiatan, $request->user());

        $validated = $request->validate([
            'catatan_pembatalan' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $kegiatan->update([
            'status' => 'dibatalkan',
            'catatan_pembatalan' => $validated['catatan_pembatalan'],
        ]);

        return back()->with('success', 'Kegiatan berhasil dibatalkan.');
    }

    private function validateKegiatan(Request $request, bool $creating = false): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal_mulai' => array_filter([
                'required',
                'date',
                $creating ? 'after_or_equal:today' : null,
            ]),
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'foto_dokumentasi' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'estimasi_biaya' => ['nullable', 'integer', 'min:0'],
            'realisasi_biaya' => ['nullable', 'integer', 'min:0'],
            'rt_id' => ['nullable', 'integer', 'exists:rts,id'],
        ]);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($user->isRwOfficial() || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function resolveActivityScope(User $user, mixed $requestedRtId): array
    {
        $rwId = $this->resolveRwId($user);

        if (! ($user->isRwOfficial() || $user->isGlobalOperator())) {
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

    private function authorizeVisible(Kegiatan $kegiatan, User $user): void
    {
        abort_unless(
            Kegiatan::visibleTo($user)->whereKey($kegiatan->getKey())->exists(),
            403
        );
    }

    private function authorizeManage(Kegiatan $kegiatan, User $user): void
    {
        abort_unless(
            $user->hasPermission('manage-kegiatan'),
            403
        );

        $this->authorizeVisible($kegiatan, $user);

        if (! ($user->isRwOfficial() || $user->isGlobalOperator())) {
            abort_unless($kegiatan->rt_id === $user->rt_id, 403);
        }
    }

    private function statuses(): array
    {
        return ['akan_datang', 'berlangsung', 'selesai', 'dibatalkan'];
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        $now = now();

        match ($status) {
            'dibatalkan' => $query->where('status', 'dibatalkan'),
            'selesai' => $query
                ->where('status', '!=', 'dibatalkan')
                ->where(function ($query) use ($now) {
                    $query->where('status', 'selesai')
                        ->orWhere('tanggal_selesai', '<=', $now);
                }),
            'berlangsung' => $query
                ->whereNotIn('status', ['dibatalkan', 'selesai'])
                ->where('tanggal_mulai', '<=', $now)
                ->where(function ($query) use ($now) {
                    $query->whereNull('tanggal_selesai')
                        ->orWhere('tanggal_selesai', '>', $now);
                }),
            'akan_datang' => $query
                ->whereNotIn('status', ['dibatalkan', 'selesai'])
                ->where('tanggal_mulai', '>', $now),
        };
    }
}
