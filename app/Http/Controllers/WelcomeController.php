<?php

namespace App\Http\Controllers;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\KartuKeluarga;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Warga;

class WelcomeController extends Controller
{
    public function index()
    {
        $kasMasuk = KasMasuk::sum('jumlah');
        $kasKeluar = KasKeluar::sum('jumlah');
        $saldo = $kasMasuk - $kasKeluar;

        $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
        $totalRumah = Rumah::count();
        $totalKK = KartuKeluarga::count();
        $totalKepalaKeluarga = Warga::where('status_dalam_kk', 'kepala_keluarga')->count();
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

        $iuranPerKK = KasMasuk::selectRaw('kartu_keluargas.no_kk, kartu_keluargas.nama_kepala_keluarga as kepala_keluarga, SUM(kas_masuks.jumlah) as total_iuran')
            ->join('users', 'kas_masuks.user_id', '=', 'users.id')
            ->join('wargas', 'users.id', '=', 'wargas.user_id')
            ->join('kartu_keluargas', 'wargas.kartu_keluarga_id', '=', 'kartu_keluargas.id')
            ->groupBy('kartu_keluargas.no_kk', 'kartu_keluargas.nama_kepala_keluarga')
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
