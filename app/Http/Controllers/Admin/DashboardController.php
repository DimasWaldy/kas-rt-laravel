<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Pengaduan;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $chartMode = $request->query('chart', 'monthly') === 'daily' ? 'daily' : 'monthly';

        $stats = Cache::remember('admin.dashboard.stats', 300, function () {
            $totalKasMasuk = KasMasuk::sum('jumlah');
            $totalKasKeluar = KasKeluar::sum('jumlah');
            $saldoAkhir = $totalKasMasuk - $totalKasKeluar;

            $bulanSekarang = now()->startOfMonth();
            $masukBulanIni = KasMasuk::whereDate('tanggal', '>=', $bulanSekarang)
                ->sum('jumlah');
            $keluarBulanIni = KasKeluar::whereDate('tanggal', '>=', $bulanSekarang)
                ->sum('jumlah');

            $chartDataMonthly = $this->getMonthlyChartData();
            $chartDataDaily = $this->getDailyChartData();

            $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
            $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();

            $wargaAktifBulanIni = Tagihan::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->where('status', 'lunas')
                ->count();

            $wargaBelumBayarBulanIni = Tagihan::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->where('status', '!=', 'lunas')
                ->count();

            $topWarga = KasMasuk::selectRaw('users.id, users.name, SUM(kas_masuks.jumlah) as total_iuran')
                ->join('users', 'kas_masuks.user_id', '=', 'users.id')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_iuran')
                ->limit(5)
                ->get();

            $topWargaBulanIni = KasMasuk::selectRaw('users.id, users.name, SUM(kas_masuks.jumlah) as total_iuran')
                ->join('users', 'kas_masuks.user_id', '=', 'users.id')
                ->whereDate('kas_masuks.tanggal', '>=', $bulanSekarang)
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_iuran')
                ->limit(5)
                ->get();

            $transaksiTerbaru = KasMasuk::with('user')
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get();

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

            $tagihanBulanIni = Tagihan::with(['user', 'rumah'])
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->get();

            $rumahBelumBayarBulanIni = $this->rumahBelumBayar($tagihanBulanIni);
            $rumahBelumBayarCount = $rumahBelumBayarBulanIni->count();

            $tagihanJatuhTempo = Tagihan::with(['user', 'rumah'])
                ->whereIn('status', ['belum_bayar', 'failed', 'pending_transfer', 'pending_offline'])
                ->get()
                ->filter(fn (Tagihan $tagihan) => $tagihan->isDueSoon() || $tagihan->isOverdue())
                ->sortBy(fn (Tagihan $tagihan) => $tagihan->due_date)
                ->take(6)
                ->values();

            $kasKeluarTerbesarBulanIni = KasKeluar::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->orderByDesc('jumlah')
                ->limit(5)
                ->get();

            $netBulanIni = $masukBulanIni - $keluarBulanIni;
            $totalRumahAktif = Rumah::where('status', 'aktif')->count();

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

            return compact(
                'totalKasMasuk',
                'totalKasKeluar',
                'saldoAkhir',
                'masukBulanIni',
                'keluarBulanIni',
                'chartDataMonthly',
                'chartDataDaily',
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
                'rumahBelumBayarBulanIni',
                'rumahBelumBayarCount',
                'tagihanJatuhTempo',
                'kasKeluarTerbesarBulanIni',
                'netBulanIni',
                'totalRumahAktif',
                'pengaduanPending',
                'pengaduanProses',
                'pengaduanSelesai',
                'pengaduanTerbaru',
                'tagihanMenungguVerifikasi'
            );
        });

        $stats['chartData'] = $chartMode === 'daily'
            ? $stats['chartDataDaily']
            : $stats['chartDataMonthly'];

        unset($stats['chartDataDaily'], $stats['chartDataMonthly']);
        $stats['chartMode'] = $chartMode;

        return view('admin.dashboard', $stats);
    }

    private function getMonthlyChartData(): array
    {
        $months = [];
        $masukData = [];
        $keluarData = [];
        $start = now()->subMonths(11)->startOfMonth();
        $end = now()->endOfMonth();
        $monthExpression = $this->monthGroupExpression();

        $masukByMonth = KasMasuk::selectRaw($monthExpression . ' as periode, SUM(jumlah) as total')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw($monthExpression))
            ->pluck('total', 'periode');

        $keluarByMonth = KasKeluar::selectRaw($monthExpression . ' as periode, SUM(jumlah) as total')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw($monthExpression))
            ->pluck('total', 'periode');

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $periode = $date->format('Y-m');

            $months[] = $date->format('M Y');
            $masukData[] = (int) ($masukByMonth[$periode] ?? 0);
            $keluarData[] = (int) ($keluarByMonth[$periode] ?? 0);
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
        $end = now()->endOfMonth();
        $daysInMonth = now()->daysInMonth;

        $masukByDate = KasMasuk::selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->pluck('total', 'tanggal');

        $keluarByDate = KasKeluar::selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->pluck('total', 'tanggal');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $start->copy()->day($day);
            $tanggal = $date->toDateString();
            $labels[] = $date->format('d M');
            $masukData[] = (int) ($masukByDate[$tanggal] ?? 0);
            $keluarData[] = (int) ($keluarByDate[$tanggal] ?? 0);
        }

        return [
            'label' => 'Harian Bulan Ini',
            'months' => $labels,
            'masukData' => $masukData,
            'keluarData' => $keluarData,
        ];
    }

    private function rumahBelumBayar(Collection $tagihanBulanIni): Collection
    {
        return $tagihanBulanIni
            ->groupBy(fn (Tagihan $tagihan) => $tagihan->rumah_id ? 'rumah-' . $tagihan->rumah_id : 'user-' . $tagihan->user_id)
            ->map(function (Collection $items) {
                $first = $items->first();

                return (object) [
                    'rumah' => $first->rumah,
                    'user' => $first->user,
                    'total' => (int) $items->sum('total'),
                    'belum_lunas' => $items->where('status', '!=', 'lunas')->count(),
                    'jumlah_tagihan' => $items->count(),
                    'status' => $items->contains(fn (Tagihan $tagihan) => in_array($tagihan->status, ['pending_transfer', 'pending_offline'], true))
                        ? 'Menunggu Verifikasi'
                        : 'Belum Bayar',
                ];
            })
            ->filter(fn ($item) => $item->belum_lunas > 0)
            ->sortByDesc('total')
            ->values();
    }

    private function monthGroupExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', tanggal)"
            : "DATE_FORMAT(tanggal, '%Y-%m')";
    }
}
