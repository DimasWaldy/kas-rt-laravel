<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KasKeluarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y'));

        $data = KasKeluar::query()
            ->visibleTo($request->user())
            ->when($search, function ($query) use ($search) {
                $query->where('keterangan', 'like', '%' . $search . '%');
            })
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->whereYear('tanggal', $tahun)
            ->latest('tanggal')
            ->get();

        return view('kas_keluar.index', compact('data'));
    }

    public function create()
    {
        return view('kas_keluar.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keterangan' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'bukti' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $path = null;
        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('kaskeluar-bukti', 'local');
        }

        KasKeluar::create([
            'rt_id' => $request->user()->rt_id,
            'keterangan' => $validated['keterangan'],
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
            'bukti' => $path,
        ]);

        return redirect()->route('kas-keluar.index')
            ->with('success', 'Data pengeluaran berhasil dicatat.');
    }

    public function bukti(KasKeluar $kasKeluar)
    {
        abort_unless($kasKeluar->isVisibleTo(request()->user()), 403);

        if (! $kasKeluar->bukti) {
            abort(404);
        }

        abort_unless(Storage::disk('local')->exists($kasKeluar->bukti), 404);

        return Storage::disk('local')->response($kasKeluar->bukti);
    }
}
