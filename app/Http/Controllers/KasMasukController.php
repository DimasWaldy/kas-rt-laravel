<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKasMasukRequest;
use App\Models\KasMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KasMasukController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y'));
        $filter = $request->query('filter', 'terbaru');

        $query = KasMasuk::query()
            ->visibleTo($request->user())
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

    public function store(StoreKasMasukRequest $request)
    {
        $validated = $request->validated();

        KasMasuk::create([
            'user_id' => auth()->id(),
            'rt_id' => $request->user()->rt_id,
            'keterangan' => $validated['keterangan'],
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
        ]);

        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.dashboard.stats.v2');
        Cache::forget('admin.dashboard.stats.v3');
        Cache::forget('dashboard.stats.user.' . auth()->id());
        Cache::forget('dashboard.stats.user.v2.' . auth()->id());

        return redirect()->route('kas-masuk.index')
            ->with('success', 'Data kas masuk berhasil dicatat.');
    }
}
