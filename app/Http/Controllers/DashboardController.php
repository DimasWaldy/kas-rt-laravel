<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $kasMasuk = KasMasuk::sum('jumlah');
        $kasKeluar = KasKeluar::sum('jumlah');

        $tanggal = KasMasuk::pluck('tanggal');
        $dataMasuk = KasMasuk::pluck('jumlah');
        $dataKeluar = KasKeluar::pluck('jumlah');

        $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
        $totalKK = User::whereNotNull('no_kk')->distinct('no_kk')->count('no_kk');
        $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();
        $totalRegistrations = User::count();
        $totalWargaByKK = User::where('is_kepala_keluarga', true)->sum('jumlah_anggota_keluarga');

        $activeKKIds = KasMasuk::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->pluck('user_id')
            ->unique();

        $kepalaKeluargaAktif = User::whereIn('id', $activeKKIds)
            ->where('is_kepala_keluarga', true)
            ->count();

        $keluargaBelumBayar = User::where('is_kepala_keluarga', true)
            ->whereNotIn('id', $activeKKIds)
            ->count();

        $iuranPerKK = KasMasuk::selectRaw('users.no_kk, users.name as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->groupBy('users.no_kk', 'users.name')
            ->orderByDesc('total_iuran')
            ->limit(5)
            ->get();

        $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->limit(5)
            ->get();

        $dueSoon = Tagihan::whereIn('status', ['belum_bayar', 'pending_transfer', 'pending_offline'])
            ->get()
            ->filter(fn($tagihan) => $tagihan->isDueSoon())
            ->count();

        $overdueCount = Tagihan::whereIn('status', ['belum_bayar', 'pending_transfer', 'pending_offline'])
            ->get()
            ->filter(fn($tagihan) => $tagihan->isOverdue())
            ->count();

        $totalPaidTagihan = Tagihan::where('status', 'lunas')->count();
        $totalUnpaidTagihan = Tagihan::whereIn('status', ['belum_bayar', 'pending_transfer', 'pending_offline'])->count();
        $monthlyRevenue = KasMasuk::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('jumlah');
        $pendingTransferCount = Tagihan::where('status', 'pending_transfer')->count();

        $topKKIuran = KasMasuk::selectRaw('users.no_kk, users.name as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->groupBy('users.no_kk', 'users.name')
            ->orderByDesc('total_iuran')
            ->limit(5)
            ->get();

        $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->limit(5)
            ->get();

        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'kasMasuk',
            'kasKeluar',
            'tanggal',
            'dataMasuk',
            'dataKeluar',
            'leaderboard',
            'totalWarga',
            'totalKK',
            'totalKepalaKeluarga',
            'totalRegistrations',
            'totalWargaByKK',
            'kepalaKeluargaAktif',
            'keluargaBelumBayar',
            'iuranPerKK',
            'dueSoon',
            'overdueCount',
            'totalPaidTagihan',
            'totalUnpaidTagihan',
            'monthlyRevenue',
            'pendingTransferCount',
            'topKKIuran',
            'recentAuditLogs'
        ));
    }
}
