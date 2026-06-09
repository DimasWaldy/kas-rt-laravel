<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;

class WelcomeController extends Controller
{
    public function index()
    {
        $kasMasuk = KasMasuk::sum('jumlah');
        $kasKeluar = KasKeluar::sum('jumlah');
        $saldo = $kasMasuk - $kasKeluar;

        $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
        $totalRumah = Rumah::count();
        $totalKK = User::whereNotNull('no_kk')->distinct('no_kk')->count('no_kk');
        $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();
        $totalRegistrations = User::count();
        $totalWargaByKK = $totalWarga;

        $rumahAktifBulanIni = Tagihan::where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->where('status', 'lunas')
            ->whereNotNull('rumah_id')
            ->distinct('rumah_id')
            ->count('rumah_id');

        $kepalaKeluargaAktif = $totalRumah ? $rumahAktifBulanIni : 0;
        $keluargaBelumBayar = $totalRumah ? max($totalRumah - $rumahAktifBulanIni, 0) : 0;

        $iuranPerKK = KasMasuk::selectRaw('users.no_kk, users.name as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->groupBy('users.no_kk', 'users.name')
            ->orderByDesc('total_iuran')
            ->limit(5)
            ->get();

        $recentMasuk = KasMasuk::latest()->take(3)->get();
        $recentKeluar = KasKeluar::latest()->take(3)->get();

        $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->limit(5)
            ->get();

        return view('welcome', compact(
            'kasMasuk',
            'kasKeluar',
            'saldo',
            'totalWarga',
            'totalRumah',
            'totalKK',
            'totalKepalaKeluarga',
            'totalRegistrations',
            'totalWargaByKK',
            'kepalaKeluargaAktif',
            'keluargaBelumBayar',
            'iuranPerKK',
            'recentMasuk',
            'recentKeluar',
            'leaderboard'
        ));
    }
}
