<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('dashboard.stats.user.v2.' . Auth::id(), 300, function () {
            $kasMasuk = KasMasuk::sum('jumlah');
            $kasKeluar = KasKeluar::sum('jumlah');

            $tanggal = KasMasuk::pluck('tanggal')->all();
            $dataMasuk = KasMasuk::pluck('jumlah')->map(fn ($jumlah) => (int) $jumlah)->all();
            $dataKeluar = KasKeluar::pluck('jumlah')->map(fn ($jumlah) => (int) $jumlah)->all();

            $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
            $totalRumah = Rumah::count();
            $totalKK = $totalRumah ?: User::whereNotNull('no_kk')->distinct('no_kk')->count('no_kk');
            $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();
            $totalRegistrations = User::count();
            $totalWargaByKK = $totalWarga;

            $rumahAktifBulanIni = Tagihan::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->where('status', 'lunas')
                ->whereNotNull('rumah_id')
                ->distinct('rumah_id')
                ->count('rumah_id');

            $kepalaKeluargaAktif = $totalRumah
                ? $rumahAktifBulanIni
                : User::whereIn('id', KasMasuk::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->pluck('user_id')->unique())
                    ->where('is_kepala_keluarga', true)
                    ->count();

            $keluargaBelumBayar = $totalRumah
                ? max($totalRumah - $rumahAktifBulanIni, 0)
                : User::where('is_kepala_keluarga', true)
                    ->whereNotIn('id', KasMasuk::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->pluck('user_id')->unique())
                    ->count();

            $iuranPerKK = KasMasuk::selectRaw('users.no_kk, users.name as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
                ->join('users', 'kas_masuks.user_id', '=', 'users.id')
                ->groupBy('users.no_kk', 'users.name')
                ->orderByDesc('total_iuran')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'no_kk' => $item->no_kk,
                    'kepala_keluarga' => $item->kepala_keluarga,
                    'total_iuran' => (int) $item->total_iuran,
                ])
                ->all();

            $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->with('user')
                ->limit(5)
                ->get()
                ->map(fn (KasMasuk $item) => [
                    'user_name' => $item->user?->name,
                    'total' => (int) $item->total,
                ])
                ->all();

            $dueSoon = Tagihan::whereIn('status', ['belum_bayar', 'failed', 'pending_transfer', 'pending_offline'])
                ->get()
                ->filter(fn($tagihan) => $tagihan->isDueSoon())
                ->count();

            $overdueCount = Tagihan::whereIn('status', ['belum_bayar', 'failed', 'pending_transfer', 'pending_offline'])
                ->get()
                ->filter(fn($tagihan) => $tagihan->isOverdue())
                ->count();

            $totalPaidTagihan = Tagihan::where('status', 'lunas')->count();
            $totalUnpaidTagihan = Tagihan::whereIn('status', ['belum_bayar', 'failed', 'pending_transfer', 'pending_offline'])->count();
            $monthlyRevenue = KasMasuk::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');
            $pendingTransferCount = Tagihan::where('status', 'pending_transfer')->count();

            $user = Auth::user();
            $statusOwnerId = $user->id;
            $statusQuery = Tagihan::query();

            if ($user->rumah_id) {
                $statusQuery->where('rumah_id', $user->rumah_id);
            } elseif (! $user->is_kepala_keluarga && filled($user->no_kk)) {
                $statusOwnerId = User::where('no_kk', $user->no_kk)
                    ->where('is_kepala_keluarga', true)
                    ->value('id') ?? $user->id;
                $statusQuery->where('user_id', $statusOwnerId);
            } else {
                $statusQuery->where('user_id', $statusOwnerId);
            }

            $userStatus = $statusQuery
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->value('status');

            $topKKIuran = KasMasuk::selectRaw('COALESCE(rumahs.kode_rumah, users.no_kk) as no_kk, COALESCE(rumahs.alamat, users.name) as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
                ->leftJoin('tagihans', 'kas_masuks.tagihan_id', '=', 'tagihans.id')
                ->leftJoin('rumahs', 'tagihans.rumah_id', '=', 'rumahs.id')
                ->join('users', 'kas_masuks.user_id', '=', 'users.id')
                ->groupBy('rumahs.kode_rumah', 'rumahs.alamat', 'users.no_kk', 'users.name')
                ->orderByDesc('total_iuran')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'no_kk' => $item->no_kk,
                    'kepala_keluarga' => $item->kepala_keluarga,
                    'total_iuran' => (int) $item->total_iuran,
                ])
                ->all();

            $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->with('user')
                ->limit(5)
                ->get()
                ->map(fn (KasMasuk $item) => [
                    'user_name' => $item->user?->name,
                    'total' => (int) $item->total,
                ])
                ->all();

            $recentAuditLogs = AuditLog::with('user')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'event' => $log->event,
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'notes' => $log->notes,
                    'user_name' => $log->user?->name,
                ])
                ->all();

            return compact(
                'kasMasuk',
                'kasKeluar',
                'tanggal',
                'dataMasuk',
                'dataKeluar',
                'leaderboard',
                'totalWarga',
                'totalKK',
                'totalRumah',
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
                'userStatus',
                'topKKIuran',
                'recentAuditLogs'
            );
        });

        return view('dashboard', $stats);
    }
}
