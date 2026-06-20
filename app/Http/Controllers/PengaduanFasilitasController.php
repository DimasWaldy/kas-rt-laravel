<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\PengaduanFasilitas;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PengaduanFasilitasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-fasilitas');

        $query = PengaduanFasilitas::with(['fasilitas.rt', 'pelapor', 'tindakLanjutOleh'])
            ->whereHas('fasilitas', function (Builder $query) use ($rwId, $user) {
                $query->where('rw_id', $rwId)
                    ->when(! $user->canAccessAllRts(), function (Builder $query) use ($user) {
                        $query->whereNull('rt_id');
                        $query->orWhere('rt_id', $user->rt_id);
                    });
            })
            ->latest();

        if (! $canManage) {
            $query->where('pelapor_id', $user->id);
        }

        $status = $request->string('status')->toString();
        if (in_array($status, $this->statuses(), true)) {
            $query->where('status', $status);
        }

        return view('pengaduan_fasilitas.index', [
            'pengaduans' => $query->paginate(12)->withQueryString(),
            'status' => $status,
            'canManage' => $canManage,
        ]);
    }

    public function create(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $fasilitas = Fasilitas::with('rt')
            ->where('rw_id', $rwId)
            ->when(! $request->user()->canAccessAllRts(), function (Builder $query) use ($request) {
                $query->whereNull('rt_id');
                $query->orWhere('rt_id', $request->user()->rt_id);
            })
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        $selectedFasilitasId = $request->integer('fasilitas_id') ?: null;

        return view('pengaduan_fasilitas.create', compact('fasilitas', 'selectedFasilitasId'));
    }

    public function store(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());

        $validated = $request->validate([
            'fasilitas_id' => [
                'required',
                'integer',
                Rule::exists('fasilitas', 'id')->where('rw_id', $rwId)->where('is_active', true),
            ],
            'jenis_masalah' => ['required', Rule::in($this->problemTypes())],
            'deskripsi' => ['required', 'string', 'min:10', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $fasilitas = Fasilitas::whereKey($validated['fasilitas_id'])
            ->where('rw_id', $rwId)
            ->firstOrFail();
        $this->authorizeFacilityVisible($request->user(), $fasilitas);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('pengaduan-fasilitas', 'local');
        }

        $pengaduan = PengaduanFasilitas::create([
            ...$validated,
            'pelapor_id' => Auth::id(),
            'status' => 'dilaporkan',
        ]);

        if (in_array($pengaduan->jenis_masalah, ['rusak', 'mati'], true)) {
            $fasilitas->update(['kondisi' => 'perlu_perhatian']);
        }

        return redirect()->route('pengaduan-fasilitas.show', $pengaduan)
            ->with('success', 'Laporan fasilitas berhasil dikirim.');
    }

    public function show(Request $request, PengaduanFasilitas $pengaduan)
    {
        $this->authorizeVisible($request->user(), $pengaduan);
        $pengaduan->load(['fasilitas.rt', 'fasilitas.rw', 'pelapor', 'tindakLanjutOleh']);

        return view('pengaduan_fasilitas.show', compact('pengaduan'));
    }

    public function foto(Request $request, PengaduanFasilitas $pengaduan)
    {
        $this->authorizeVisible($request->user(), $pengaduan);
        abort_unless($pengaduan->foto && Storage::disk('local')->exists($pengaduan->foto), 404);

        return Storage::disk('local')->response($pengaduan->foto);
    }

    public function tindakLanjut(Request $request, PengaduanFasilitas $pengaduan)
    {
        $this->authorizeManage($request->user(), $pengaduan);

        $validated = $request->validate([
            'catatan_tindak_lanjut' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $pengaduan->update([
            'status' => 'ditindaklanjuti',
            'ditindaklanjuti_oleh' => $request->user()->id,
            'catatan_tindak_lanjut' => $validated['catatan_tindak_lanjut'],
        ]);

        return back()->with('success', 'Pengaduan fasilitas ditandai sedang ditindaklanjuti.');
    }

    public function selesaikan(Request $request, PengaduanFasilitas $pengaduan)
    {
        $this->authorizeManage($request->user(), $pengaduan);

        $pengaduan->update([
            'status' => 'selesai',
            'ditindaklanjuti_oleh' => $request->user()->id,
            'tanggal_selesai' => now(),
            'catatan_tindak_lanjut' => $request->string('catatan_tindak_lanjut')->toString()
                ?: $pengaduan->catatan_tindak_lanjut,
        ]);

        if ($request->boolean('update_kondisi_baik')
            && in_array($pengaduan->fasilitas->kondisi, ['perlu_perhatian', 'rusak'], true)) {
            $pengaduan->fasilitas->update(['kondisi' => 'baik']);
        }

        return back()->with('success', 'Pengaduan fasilitas berhasil diselesaikan.');
    }

    public function tolak(Request $request, PengaduanFasilitas $pengaduan)
    {
        $this->authorizeManage($request->user(), $pengaduan);

        $validated = $request->validate([
            'catatan_tindak_lanjut' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $pengaduan->update([
            'status' => 'ditolak',
            'ditindaklanjuti_oleh' => $request->user()->id,
            'catatan_tindak_lanjut' => $validated['catatan_tindak_lanjut'],
        ]);

        return back()->with('success', 'Pengaduan fasilitas berhasil ditolak.');
    }

    private function authorizeVisible(User $user, PengaduanFasilitas $pengaduan): void
    {
        $pengaduan->loadMissing('fasilitas');
        $this->authorizeFacilityVisible($user, $pengaduan->fasilitas);

        if (! $user->hasPermission('manage-fasilitas')) {
            abort_unless($pengaduan->pelapor_id === $user->id, 403);
        }
    }

    private function authorizeManage(User $user, PengaduanFasilitas $pengaduan): void
    {
        abort_unless($user->hasPermission('manage-fasilitas'), 403);
        $pengaduan->loadMissing('fasilitas');
        $this->authorizeFacilityVisible($user, $pengaduan->fasilitas);
    }

    private function authorizeFacilityVisible(User $user, Fasilitas $fasilitas): void
    {
        if ($user->isGlobalOperator()) {
            return;
        }

        abort_unless($fasilitas->rw_id === $this->resolveRwId($user), 403);

        if (! $user->canAccessAllRts() && $fasilitas->rt_id) {
            abort_unless($fasilitas->rt_id === $user->rt_id, 403);
        }
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

    private function problemTypes(): array
    {
        return ['rusak', 'mati', 'kotor', 'hilang', 'lainnya'];
    }

    private function statuses(): array
    {
        return ['dilaporkan', 'ditindaklanjuti', 'selesai', 'ditolak'];
    }
}
