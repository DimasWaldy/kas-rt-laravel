<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use Illuminate\Http\Request;

class KasKeluarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y'));

        $data = KasKeluar::query()
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
            $path = $request->file('bukti')->store('uploads', 'public');
        }

        KasKeluar::create([
            'keterangan' => $validated['keterangan'],
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
            'bukti' => $path,
        ]);

        return redirect()->route('kas-keluar.index')
            ->with('success', 'Data pengeluaran berhasil dicatat.');
    }
}
