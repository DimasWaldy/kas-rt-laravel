<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use Illuminate\Http\Request;

class LaporanKasController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->canManageFinance()) {
            abort(403);
        }

        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $kasMasuk = KasMasuk::with('user')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->latest('tanggal')
            ->get();

        $kasKeluar = KasKeluar::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->latest('tanggal')
            ->get();

        $totalMasuk = $kasMasuk->sum('jumlah');
        $totalKeluar = $kasKeluar->sum('jumlah');
        $saldoPeriode = $totalMasuk - $totalKeluar;

        $riwayat = $kasMasuk
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'tipe' => 'Masuk',
                'keterangan' => $item->keterangan,
                'jumlah' => $item->jumlah,
                'user' => $item->user?->name ?? 'Anonim',
            ])
            ->concat($kasKeluar->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'tipe' => 'Keluar',
                'keterangan' => $item->keterangan,
                'jumlah' => $item->jumlah,
                'user' => '-',
            ]))
            ->sortByDesc('tanggal')
            ->values();

        return view('laporan_kas.index', compact(
            'bulan',
            'tahun',
            'kasMasuk',
            'kasKeluar',
            'totalMasuk',
            'totalKeluar',
            'saldoPeriode',
            'riwayat',
        ));
    }
}
