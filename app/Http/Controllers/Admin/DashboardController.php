<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Pengaduan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $chartMode = $request->query('chart', 'monthly') === 'daily' ? 'daily' : 'monthly';

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

        $chartData = $chartMode === 'daily'
            ? $this->getDailyChartData()
            : $this->getMonthlyChartData();

        // Statistik Warga
        $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
        $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();

        // Warga aktif bulan ini (yang sudah bayar)
        $wargaAktifBulanIni = Tagihan::where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->where('status', 'lunas')
            ->count();

        // Warga belum bayar bulan ini
        $wargaBelumBayarBulanIni = Tagihan::where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->where('status', '!=', 'lunas')
            ->count();

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
        $pendingTransferCount = Tagihan::where('status', 'pending_transfer')->count();
        $pendingOfflineCount = Tagihan::where('status', 'pending_offline')->count();
        $tagihanBelumLunasBulanIni = Tagihan::where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->where('status', '!=', 'lunas')
            ->count();
        $nominalTagihanTertunggak = Tagihan::where('status', '!=', 'lunas')->sum('total');

        // Statistik pengaduan
        $pengaduanPending = Pengaduan::where('status', 'pending')->count();
        $pengaduanProses = Pengaduan::where('status', 'proses')->count();
        $pengaduanSelesai = Pengaduan::where('status', 'selesai')->count();
        $pengaduanTerbaru = Pengaduan::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $tagihanMenungguVerifikasi = Tagihan::with('user')
            ->whereIn('status', ['pending_transfer', 'pending_offline'])
            ->latest()
            ->limit(5)
            ->get();

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
            'tagihanSudahLunas',
            'pendingTransferCount',
            'pendingOfflineCount',
            'tagihanBelumLunasBulanIni',
            'nominalTagihanTertunggak',
            'pengaduanPending',
            'pengaduanProses',
            'pengaduanSelesai',
            'pengaduanTerbaru',
            'tagihanMenungguVerifikasi',
            'chartMode'
        ));
    }

    private function getMonthlyChartData(): array
    {
        $months = [];
        $masukData = [];
        $keluarData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $months[] = $date->format('M Y');

            $masuk = KasMasuk::whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');
            $keluar = KasKeluar::whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');

            $masukData[] = $masuk;
            $keluarData[] = $keluar;
        }

        return [
            'label' => '12 Bulan Terakhir',
            'months' => $months,
            'masukData' => $masukData,
            'keluarData' => $keluarData,
        ];
    }

    private function getDailyChartData(): array
    {
        $labels = [];
        $masukData = [];
        $keluarData = [];
        $start = now()->startOfMonth();
        $daysInMonth = now()->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $start->copy()->day($day);
            $labels[] = $date->format('d M');

            $masukData[] = KasMasuk::whereDate('tanggal', $date)->sum('jumlah');
            $keluarData[] = KasKeluar::whereDate('tanggal', $date)->sum('jumlah');
        }

        return [
            'label' => 'Harian Bulan Ini',
            'months' => $labels,
            'masukData' => $masukData,
            'keluarData' => $keluarData,
        ];
    }
}
