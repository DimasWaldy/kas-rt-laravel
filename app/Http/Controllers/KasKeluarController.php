<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KasKeluar;
use Illuminate\Support\Facades\Storage;

class KasKeluarController extends Controller
{
    public function index(Request $request) // Tambahin Request $request di sini
    {
        // 1. Tangkap input dari filter
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun', date('Y')); // Default tahun sekarang

        // 2. Query data dengan kondisi (Eloquents When)
        $data = KasKeluar::query()
            ->when($search, function ($query) use ($search) {
                $query->where('keterangan', 'like', '%' . $search . '%');
            })
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->whereYear('tanggal', $tahun)
            ->latest('tanggal') // Urutkan berdasarkan tanggal terbaru
            ->get();

        return view('kas_keluar.index', compact('data'));
    }

    public function create()
    {
        return view('kas_keluar.create');
    }

    public function store(Request $request)
    {
        // Validasi simpel biar aman
        $request->validate([
            'keterangan' => 'required|string',
            'jumlah' => 'required|numeric',
            'tanggal' => 'required|date',
            'bukti' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $path = null;

        // Gunakan Storage agar link asset('storage/...') di view lu jalan
        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('uploads', 'public');
        }

        KasKeluar::create([
            'user_id' => auth()->id(),
            'keterangan' => $request->keterangan,
            'jumlah' => $request->jumlah,
            'tanggal' => $request->tanggal,
            'bukti' => $path // Path yang disimpan: "uploads/namafile.jpg"
        ]);

        return redirect('/kas-keluar')
            ->with('success', 'Data pengeluaran berhasil dicatat! 💸');
    }
}
