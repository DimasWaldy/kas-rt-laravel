<?php

namespace App\Http\Controllers;

use App\Models\IuranBulanan;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Rumah;
use App\Models\Tagihan;
use Illuminate\Http\Request;

class DemoUtsController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->canManageFinance()) {
            abort(403);
        }

        $bulan = now()->month;
        $tahun = now()->year;

        $totalRumah = Rumah::where('status', 'aktif')->count();
        $komponenIuran = IuranBulanan::where('bulan', $bulan)->where('tahun', $tahun)->count();
        $tagihanBulanIni = Tagihan::where('bulan', $bulan)->where('tahun', $tahun)->count();
        $tagihanLunas = Tagihan::where('bulan', $bulan)->where('tahun', $tahun)->where('status', 'lunas')->count();
        $menungguVerifikasi = Tagihan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereIn('status', ['pending_transfer', 'pending_offline'])
            ->count();

        $kasMasukBulanIni = KasMasuk::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah');
        $kasKeluarBulanIni = KasKeluar::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah');

        return view('demo_uts.index', compact(
            'bulan',
            'tahun',
            'totalRumah',
            'komponenIuran',
            'tagihanBulanIni',
            'tagihanLunas',
            'menungguVerifikasi',
            'kasMasukBulanIni',
            'kasKeluarBulanIni',
        ));
    }
}
