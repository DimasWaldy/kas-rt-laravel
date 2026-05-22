<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Statistik Total
        $totalKasMasuk = KasMasuk::sum('jumlah');
        $totalKasKeluar = KasKeluar::sum('jumlah');
        $saldoAkhir = $totalKasMasuk - $totalKasKeluar;

        // Statistik Bulanan (bulan saat ini)
        $bulanSekarang = now()->startOfMonth();
        $masukBulanIni = KasMasuk::whereDate('tanggal', '>=', $bulanSekarang)
            ->sum('jumlah');
        $keluarBulanIni = KasKeluar::whereDate('tanggal', '>=', $bulanSekarang)
            ->sum('jumlah');

        // Data untuk chart 12 bulan terakhir
        $chartData = $this->getMonthlyChartData();

        // Statistik Warga
        $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
        $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();

        // Warga aktif bulan ini (yang sudah bayar)
        $wargaAktifBulanIni = KasMasuk::whereDate('tanggal', '>=', $bulanSekarang)
            ->distinct('user_id')
            ->count('user_id');

        // Warga belum bayar bulan ini
        $wargaBelumBayarBulanIni = $totalKepalaKeluarga - $wargaAktifBulanIni;

        // Top 5 warga berdasarkan total iuran sepanjang masa
        $topWarga = KasMasuk::selectRaw('users.id, users.name, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_iuran')
            ->limit(5)
            ->get();

        // Top 5 warga bulan ini
        $topWargaBulanIni = KasMasuk::selectRaw('users.id, users.name, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->whereDate('kas_masuks.tanggal', '>=', $bulanSekarang)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_iuran')
            ->limit(5)
            ->get();

        // Transaksi terbaru (10 data)
        $transaksiTerbaru = KasMasuk::with('user')
            ->orderBy('tanggal', 'desc')
            ->limit(10)
            ->get();

        // Statistik Tagihan
        $totalTagihan = Tagihan::count();
        $tagihanBelumLunas = Tagihan::where('status', '!=', 'lunas')->count();
        $tagihanSudahLunas = Tagihan::where('status', 'lunas')->count();

        return view('admin.dashboard', compact(
            'totalKasMasuk',
            'totalKasKeluar',
            'saldoAkhir',
            'masukBulanIni',
            'keluarBulanIni',
            'chartData',
            'totalWarga',
            'totalKepalaKeluarga',
            'wargaAktifBulanIni',
            'wargaBelumBayarBulanIni',
            'topWarga',
            'topWargaBulanIni',
            'transaksiTerbaru',
            'totalTagihan',
            'tagihanBelumLunas',
            'tagihanSudahLunas'
        ));
    }

    private function getMonthlyChartData(): array
    {
        $months = [];
        $masukData = [];
        $keluarData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->startOfMonth();
            $monthEnd = $date->endOfMonth();

            $months[] = $date->format('M Y');

            $masuk = KasMasuk::whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');
            $keluar = KasKeluar::whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');

            $masukData[] = $masuk;
            $keluarData[] = $keluar;
        }

        return [
            'months' => $months,
            'masukData' => $masukData,
            'keluarData' => $keluarData,
        ];
    }
}
