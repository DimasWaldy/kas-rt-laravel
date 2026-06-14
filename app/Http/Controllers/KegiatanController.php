<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $query = Kegiatan::with(['rw', 'creator'])
            ->withCount('hadirs')
            ->where('rw_id', $rwId)
            ->orderByDesc('tanggal_mulai');

        $status = $request->string('status')->toString();
        if (in_array($status, $this->statuses(), true)) {
            $query->where('status', $status);
        }

        return view('kegiatan.index', [
            'kegiatans' => $query->paginate(12)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function create()
    {
        abort_unless(Auth::user()->hasPermission('manage-kegiatan'), 403);
        $this->resolveRwId(Auth::user());

        return view('kegiatan.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-kegiatan'), 403);
        $validated = $this->validateKegiatan($request);
        $rwId = $this->resolveRwId($request->user());

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('kegiatan', 'local')
            : null;

        $kegiatan = Kegiatan::create([
            ...$validated,
            'rw_id' => $rwId,
            'created_by' => Auth::id(),
            'foto' => $fotoPath,
            'estimasi_biaya' => $validated['estimasi_biaya'] ?? 0,
            'realisasi_biaya' => $validated['realisasi_biaya'] ?? 0,
            'status' => 'akan_datang',
        ]);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan RW berhasil dibuat.');
    }

    public function show(Kegiatan $kegiatan)
    {
        $this->authorizeInRw($kegiatan, Auth::user());
        $kegiatan->load(['rw', 'creator', 'hadirs.user']);

        $sudahHadir = $kegiatan->hadirs()
            ->where('user_id', Auth::id())
            ->exists();

        return view('kegiatan.show', compact('kegiatan', 'sudahHadir'));
    }

    public function foto(Kegiatan $kegiatan)
    {
        abort_unless(Auth::check(), 403);
        $this->authorizeInRw($kegiatan, Auth::user());
        abort_unless($kegiatan->foto && Storage::disk('local')->exists($kegiatan->foto), 404);

        return Storage::disk('local')->response($kegiatan->foto);
    }

    public function konfirmasiHadir(Request $request, Kegiatan $kegiatan)
    {
        abort_unless($request->user()->hasPermission('view-kegiatan'), 403);
        $this->authorizeInRw($kegiatan, $request->user());

        if (in_array($kegiatan->status, ['dibatalkan', 'selesai'], true)) {
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
        $this->authorizeOwner($kegiatan, Auth::user());

        return view('kegiatan.edit', compact('kegiatan'));
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $this->authorizeOwner($kegiatan, $request->user());
        $validated = $this->validateKegiatan($request);

        if ($request->hasFile('foto')) {
            if ($kegiatan->foto) {
                Storage::disk('local')->delete($kegiatan->foto);
            }

            $validated['foto'] = $request->file('foto')->store('kegiatan', 'local');
        }

        $validated['estimasi_biaya'] = $validated['estimasi_biaya'] ?? 0;
        $validated['realisasi_biaya'] = $validated['realisasi_biaya'] ?? 0;
        $kegiatan->update($validated);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan RW berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        $this->authorizeOwner($kegiatan, Auth::user());

        if ($kegiatan->foto) {
            Storage::disk('local')->delete($kegiatan->foto);
        }

        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan RW berhasil dihapus.');
    }

    public function batalkan(Request $request, Kegiatan $kegiatan)
    {
        abort_unless($request->user()->hasPermission('manage-kegiatan'), 403);
        $this->authorizeInRw($kegiatan, $request->user());

        $validated = $request->validate([
            'catatan_pembatalan' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $kegiatan->update([
            'status' => 'dibatalkan',
            'catatan_pembatalan' => $validated['catatan_pembatalan'],
        ]);

        return back()->with('success', 'Kegiatan RW berhasil dibatalkan.');
    }

    private function validateKegiatan(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'estimasi_biaya' => ['nullable', 'integer', 'min:0'],
            'realisasi_biaya' => ['nullable', 'integer', 'min:0'],
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

    private function authorizeInRw(Kegiatan $kegiatan, User $user): void
    {
        abort_unless($kegiatan->rw_id === $this->resolveRwId($user), 404);
    }

    private function authorizeOwner(Kegiatan $kegiatan, User $user): void
    {
        $this->authorizeInRw($kegiatan, $user);
        abort_unless(
            $user->hasPermission('manage-kegiatan')
                && ($user->isGlobalOperator() || $kegiatan->created_by === $user->id),
            403
        );
    }

    private function statuses(): array
    {
        return ['akan_datang', 'berlangsung', 'selesai', 'dibatalkan'];
    }
}
