<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pemilihan;
use App\Models\PemilihanKandidat;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EvotingAdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Pemilihan::with(['rt', 'rw', 'kandidats', 'voters']);

        if (! $user->isGlobalOperator() && ! $user->isPengurusRw()) {
            if ($user->rt_id) {
                $query->where('scope_type', 'rt')->where('rt_id', $user->rt_id);
            }
        }

        $pemilihans = $query->latest('start_date')->paginate(10);

        return view('admin.evoting.index', compact('pemilihans'));
    }

    public function create()
    {
        $rts = Rt::orderBy('name')->get();
        $rws = Rw::orderBy('name')->get();

        return view('admin.evoting.create', compact('rts', 'rws'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scope_type' => 'required|in:rt,rw',
            'rt_id' => 'nullable|required_if:scope_type,rt|exists:rts,id',
            'rw_id' => 'nullable|required_if:scope_type,rw|exists:rws,id',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;
        while (Pemilihan::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $pemilihan = Pemilihan::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'scope_type' => $validated['scope_type'],
            'rt_id' => $validated['scope_type'] === 'rt' ? $validated['rt_id'] : null,
            'rw_id' => $validated['scope_type'] === 'rw' ? $validated['rw_id'] : ($validated['rt_id'] ? Rt::find($validated['rt_id'])?->rw_id : null),
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'aktif',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.evoting.show', $pemilihan->slug)
            ->with('success', 'Pemilihan baru berhasil dibuat. Silakan tambahkan kandidat.');
    }

    public function show(Pemilihan $pemilihan)
    {
        $pemilihan->load(['kandidats.suaras', 'rt', 'rw', 'voters']);
        $quickCount = $pemilihan->quickCountResults();
        $totalSuara = $pemilihan->suaras()->count();

        return view('admin.evoting.show', compact('pemilihan', 'quickCount', 'totalSuara'));
    }

    public function addKandidat(Request $request, Pemilihan $pemilihan)
    {
        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1',
            'nama_calon' => 'required|string|max:255',
            'nama_wakil' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'foto' => 'nullable|image|max:2048',
        ]);

        if ($pemilihan->kandidats()->where('nomor_urut', $validated['nomor_urut'])->exists()) {
            return back()->withInput()->with('error', 'Nomor urut '.$validated['nomor_urut'].' sudah digunakan oleh kandidat lain.');
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('kandidat-evoting', 'public');
        }

        $pemilihan->kandidats()->create([
            'nomor_urut' => $validated['nomor_urut'],
            'nama_calon' => $validated['nama_calon'],
            'nama_wakil' => $validated['nama_wakil'] ?? null,
            'slogan' => $validated['slogan'] ?? null,
            'visi' => $validated['visi'] ?? null,
            'misi' => $validated['misi'] ?? null,
            'foto_path' => $fotoPath,
        ]);

        return back()->with('success', 'Kandidat berhasil ditambahkan ke pemilihan.');
    }

    public function deleteKandidat(PemilihanKandidat $kandidat)
    {
        $pemilihan = $kandidat->pemilihan;
        if ($pemilihan->suaras()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kandidat karena sudah ada suara yang masuk.');
        }

        $kandidat->delete();

        return back()->with('success', 'Kandidat berhasil dihapus.');
    }

    public function updateStatus(Request $request, Pemilihan $pemilihan)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,aktif,selesai,dibatalkan',
        ]);

        $pemilihan->update(['status' => $validated['status']]);

        return back()->with('success', 'Status pemilihan berhasil diperbarui menjadi '.ucfirst($validated['status']).'.');
    }
}
