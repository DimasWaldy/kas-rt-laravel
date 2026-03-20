<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KasMasuk;

class KasMasukController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data dari input filter di URL
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y')); // Default tahun ini

        // 2. Eksekusi Query dengan Filter
        $data = KasMasuk::query()
            ->when($search, function ($query) use ($search) {
                // Filter berdasarkan nama warga atau keterangan (jika ada kolomnya)
                // Di sini gua asumsiin filter search buat nyari nama user/warga
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })->orWhere('keterangan', 'like', '%' . $search . '%');
            })
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->whereYear('tanggal', $tahun)
            ->with('user') // Eager loading biar gak lemot (N+1 Problem)
            ->latest('tanggal')
            ->get();

        return view('kas_masuk.index', compact('data'));
    }

    public function create()
    {
        return view('kas_masuk.create');
    }

    public function store(Request $request)
    {
        KasMasuk::create([
            'user_id' => auth()->id(), // 🔥 INI YANG KEMARIN KURANG
            'keterangan' => $request->keterangan,
            'jumlah' => $request->jumlah,
            'tanggal' => $request->tanggal,
        ]);

        return redirect('/kas-masuk');
    }
}
