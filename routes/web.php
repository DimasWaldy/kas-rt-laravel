<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoUtsController;
use App\Http\Controllers\IuranBulananController;
use App\Http\Controllers\KasKeluarController;
use App\Http\Controllers\KasMasukController;
use App\Http\Controllers\LaporanKasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagihanController;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Rumah;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kas-masuk', [KasMasukController::class, 'index'])->name('kas-masuk.index');

    Route::middleware(['permission:manage-finance'])->group(function () {
        Route::get('/kas-masuk/create', [KasMasukController::class, 'create'])->name('kas-masuk.create');
        Route::post('/kas-masuk/store', [KasMasukController::class, 'store'])->name('kas-masuk.store');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kas-keluar', [KasKeluarController::class, 'index'])->name('kas-keluar.index');

    Route::middleware(['permission:manage-finance'])->group(function () {
        Route::get('/kas-keluar/create', [KasKeluarController::class, 'create'])->name('kas-keluar.create');
        Route::post('/kas-keluar/store', [KasKeluarController::class, 'store'])->name('kas-keluar.store');

        Route::get('/admin/tagihan', [TagihanController::class, 'adminIndex'])->name('tagihan.admin');
        Route::get('/tagihan/create', [TagihanController::class, 'create'])->name('tagihan.create');
        Route::post('/tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
        Route::get('/tagihan/{tagihan}/edit', [TagihanController::class, 'edit'])->name('tagihan.edit');
        Route::patch('/tagihan/{tagihan}', [TagihanController::class, 'update'])->name('tagihan.update');
        Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'destroy'])->name('tagihan.destroy');
        Route::post('/admin/tagihan/confirm', [TagihanController::class, 'confirm'])->name('tagihan.confirm');

        Route::get('/admin/iuran-bulanan', [IuranBulananController::class, 'index'])->name('iuran-bulanan.index');
        Route::get('/admin/iuran-bulanan/create', [IuranBulananController::class, 'create'])->name('iuran-bulanan.create');
        Route::post('/admin/iuran-bulanan/store', [IuranBulananController::class, 'store'])->name('iuran-bulanan.store');
        Route::post('/admin/iuran-bulanan/generate', [IuranBulananController::class, 'generateMassal'])->name('iuran-bulanan.generate');
        Route::get('/admin/laporan-kas', [LaporanKasController::class, 'index'])->name('laporan-kas.index');
        Route::get('/admin/demo-uts', [DemoUtsController::class, 'index'])->name('demo-uts.index');
    });

    Route::middleware(['permission:manage-warga'])->group(function () {
        Route::get('/admin/warga', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.warga.index');
        Route::get('/admin/warga/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.warga.create');
        Route::post('/admin/warga', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.warga.store');
        Route::get('/admin/warga/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.warga.edit');
        Route::patch('/admin/warga/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.warga.update');
        Route::delete('/admin/warga/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.warga.destroy');

        Route::get('/admin/rumah', [\App\Http\Controllers\Admin\RumahController::class, 'index'])->name('admin.rumah.index');
        Route::get('/admin/rumah/{rumah}', [\App\Http\Controllers\Admin\RumahController::class, 'show'])->name('admin.rumah.show');
        Route::get('/admin/rumah/{rumah}/edit', [\App\Http\Controllers\Admin\RumahController::class, 'edit'])->name('admin.rumah.edit');
        Route::patch('/admin/rumah/{rumah}', [\App\Http\Controllers\Admin\RumahController::class, 'update'])->name('admin.rumah.update');
        Route::post('/admin/rumah/{rumah}/warga/{user}/move', [\App\Http\Controllers\Admin\RumahController::class, 'moveWarga'])->name('admin.rumah.warga.move');
    });

    Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->middleware('permission:admin-only')
        ->name('admin.dashboard');

    Route::get('/admin', fn() => redirect()->route('admin.dashboard'))->middleware('permission:admin-only');

    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/pay', [TagihanController::class, 'pay'])->name('tagihan.pay');

    Route::resource('pengaduan', \App\Http\Controllers\PengaduanController::class)->except(['edit', 'update']);
    Route::patch('/pengaduan/{pengaduan}/status', [\App\Http\Controllers\PengaduanController::class, 'updateStatus'])
        ->middleware('permission:manage-pengaduan')
        ->name('pengaduan.status');
});

Route::get('/', function () {
    $kasMasuk = KasMasuk::sum('jumlah');
    $kasKeluar = KasKeluar::sum('jumlah');
    $saldo = $kasMasuk - $kasKeluar;

    $totalWarga = User::whereRelation('role', 'name', 'warga')->count();
    $totalRumah = Rumah::count();
    $totalKK = User::whereNotNull('no_kk')->distinct('no_kk')->count('no_kk');
    $totalKepalaKeluarga = User::where('is_kepala_keluarga', true)->count();
    $totalRegistrations = User::count();
    $totalWargaByKK = $totalWarga;

    $rumahAktifBulanIni = \App\Models\Tagihan::where('bulan', now()->month)
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
});

require __DIR__ . '/auth.php';
