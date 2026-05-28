<?php

namespace App\Http\Controllers;

use App\Models\KasMasuk;
use Illuminate\Http\Request;

class KasMasukController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y'));
        $filter = $request->query('filter', 'terbaru');

        $query = KasMasuk::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })->orWhere('keterangan', 'like', '%' . $search . '%');
                });
            })
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->whereYear('tanggal', $tahun)
            ->with(['user', 'tagihan']);

        match ($filter) {
            'terlama' => $query->oldest('tanggal'),
            'terbesar' => $query->orderByDesc('jumlah'),
            default => $query->latest('tanggal'),
        };

        $data = $query->get();

        return view('kas_masuk.index', compact('data'));
    }

    public function create()
    {
        return view('kas_masuk.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keterangan' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
        ]);

        KasMasuk::create([
            'user_id' => auth()->id(),
            'keterangan' => $validated['keterangan'],
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
        ]);

        return redirect()->route('kas-masuk.index')
            ->with('success', 'Data kas masuk berhasil dicatat.');
    }
}
