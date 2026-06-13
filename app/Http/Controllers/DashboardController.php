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
            $user = Auth::user();
            $canViewGlobalFinance = $user->hasPermission('view-finance') || $user->hasPermission('manage-finance');

            $kasMasuk = $canViewGlobalFinance ? KasMasuk::visibleTo($user)->sum('jumlah') : null;
            $kasKeluar = $canViewGlobalFinance ? KasKeluar::visibleTo($user)->sum('jumlah') : null;

            $tanggal = $canViewGlobalFinance ? KasMasuk::visibleTo($user)->pluck('tanggal')->all() : [];
            $dataMasuk = $canViewGlobalFinance
                ? KasMasuk::visibleTo($user)->pluck('jumlah')->map(fn ($jumlah) => (int) $jumlah)->all()
                : [];
            $dataKeluar = $canViewGlobalFinance
                ? KasKeluar::visibleTo($user)->pluck('jumlah')->map(fn ($jumlah) => (int) $jumlah)->all()
                : [];

            $totalWarga = User::visibleTo($user)->whereRelation('role', 'name', 'warga')->count();
            $totalRumah = Rumah::visibleTo($user)->count();
            $totalKK = $totalRumah ?: User::visibleTo($user)->whereNotNull('no_kk')->distinct('no_kk')->count('no_kk');
            $totalKepalaKeluarga = User::visibleTo($user)->where('is_kepala_keluarga', true)->count();
            $totalRegistrations = User::visibleTo($user)->count();
            $totalWargaByKK = $totalWarga;

            $rumahAktifBulanIni = $canViewGlobalFinance ? Tagihan::visibleTo($user)
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->where('status', 'lunas')
                ->whereNotNull('rumah_id')
                ->distinct('rumah_id')
                ->count('rumah_id') : 0;

            $kepalaKeluargaAktif = $canViewGlobalFinance && $totalRumah
                ? $rumahAktifBulanIni
                : ($canViewGlobalFinance ? User::visibleTo($user)->whereIn('id', KasMasuk::visibleTo($user)->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->pluck('user_id')->unique())
                    ->where('is_kepala_keluarga', true)
                    ->count() : 0);

            $keluargaBelumBayar = $canViewGlobalFinance && $totalRumah
                ? max($totalRumah - $rumahAktifBulanIni, 0)
                : ($canViewGlobalFinance ? User::visibleTo($user)->where('is_kepala_keluarga', true)
                    ->whereNotIn('id', KasMasuk::visibleTo($user)->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->pluck('user_id')->unique())
                    ->count() : 0);

            $iuranPerKK = $canViewGlobalFinance ? KasMasuk::visibleTo($user)
                ->selectRaw('users.no_kk, users.name as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
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
                ->all() : [];

            $leaderboard = $canViewGlobalFinance ? KasMasuk::visibleTo($user)
                ->selectRaw('user_id, SUM(jumlah) as total')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->with('user')
                ->limit(5)
                ->get()
                ->map(fn (KasMasuk $item) => [
                    'user_name' => $item->user?->name,
                    'total' => (int) $item->total,
                ])
                ->all() : [];

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

            $unpaidStatuses = ['belum_bayar', 'failed', 'pending_transfer', 'pending_offline'];
            $dueSoonQuery = $canViewGlobalFinance
                ? Tagihan::visibleTo($user)->whereIn('status', $unpaidStatuses)
                : (clone $statusQuery)->whereIn('status', $unpaidStatuses);

            $dueSoon = $dueSoonQuery
                ->get()
                ->filter(fn($tagihan) => $tagihan->isDueSoon())
                ->count();

            $overdueQuery = $canViewGlobalFinance
                ? Tagihan::visibleTo($user)->whereIn('status', $unpaidStatuses)
                : (clone $statusQuery)->whereIn('status', $unpaidStatuses);

            $overdueCount = $overdueQuery
                ->get()
                ->filter(fn($tagihan) => $tagihan->isOverdue())
                ->count();

            $totalPaidTagihan = $canViewGlobalFinance ? Tagihan::visibleTo($user)->where('status', 'lunas')->count() : 0;
            $totalUnpaidTagihan = $canViewGlobalFinance
                ? Tagihan::visibleTo($user)->whereIn('status', $unpaidStatuses)->count()
                : (clone $statusQuery)->whereIn('status', $unpaidStatuses)->count();
            $monthlyRevenue = $canViewGlobalFinance ? KasMasuk::visibleTo($user)->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah') : 0;
            $pendingTransferCount = $canViewGlobalFinance ? Tagihan::visibleTo($user)->where('status', 'pending_transfer')->count() : 0;

            $userStatus = $statusQuery
                ->where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->value('status');

            $topKKIuran = $canViewGlobalFinance ? KasMasuk::visibleTo($user)
                ->selectRaw('COALESCE(rumahs.kode_rumah, users.no_kk) as no_kk, COALESCE(rumahs.alamat, users.name) as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
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
                ->all() : [];

            $recentAuditLogs = $user->role_name !== 'warga' ? AuditLog::with('user')
                ->when(! $user->canAccessAllRts(), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('rt_id', $user->rt_id)))
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'event' => $log->event,
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'notes' => $log->notes,
                    'user_name' => $log->user?->name,
                ])
                ->all() : [];

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
