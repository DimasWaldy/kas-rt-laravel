<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\Tagihan;
use App\Models\User;
use App\Http\Controllers\KasMasukController;
use App\Http\Controllers\KasKeluarController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\IuranBulananController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/admin', function () {
    if (Auth::user()->role_name !== 'admin') {
        abort(403);
    }

    return "HALAMAN ADMIN 😈";
})->middleware(['auth']);



Route::middleware(['auth'])->group(function () {
    Route::get('/kas-masuk', [KasMasukController::class, 'index']);
    Route::get('/kas-masuk/create', [KasMasukController::class, 'create']);
    Route::post('/kas-masuk/store', [KasMasukController::class, 'store']);
});



Route::middleware(['auth'])->group(function () {

    // Kas Keluar
    Route::get('/kas-keluar', [KasKeluarController::class, 'index'])->name('kas-keluar.index');

    // Route khusus Admin/Bendahara
    Route::middleware(['can:admin-only'])->group(function () {
        Route::get('/kas-keluar/create', [KasKeluarController::class, 'create'])->name('kas-keluar.create');
        Route::post('/kas-keluar/store', [KasKeluarController::class, 'store'])->name('kas-keluar.store');

        Route::get('/admin/tagihan', [TagihanController::class, 'adminIndex'])->name('tagihan.admin');
        Route::get('/tagihan/create', [TagihanController::class, 'create'])->name('tagihan.create');
        Route::post('/tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
        Route::get('/tagihan/{tagihan}/edit', [TagihanController::class, 'edit'])->name('tagihan.edit');
        Route::patch('/tagihan/{tagihan}', [TagihanController::class, 'update'])->name('tagihan.update');
        Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'destroy'])->name('tagihan.destroy');
        Route::post('/admin/tagihan/confirm', [TagihanController::class, 'confirm'])->name('tagihan.confirm');

        Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/admin/iuran-bulanan', [IuranBulananController::class, 'index'])->name('iuran-bulanan.index');
        Route::get('/admin/iuran-bulanan/create', [IuranBulananController::class, 'create'])->name('iuran-bulanan.create');
        Route::post('/admin/iuran-bulanan/store', [IuranBulananController::class, 'store'])->name('iuran-bulanan.store');

        Route::get('/admin/warga', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.warga.index');
        Route::get('/admin/warga/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.warga.create');
        Route::post('/admin/warga', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.warga.store');
        Route::get('/admin/warga/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.warga.edit');
        Route::patch('/admin/warga/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.warga.update');
        Route::delete('/admin/warga/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.warga.destroy');
    });

    // Tagihan Warga
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/pay', [TagihanController::class, 'pay'])->name('tagihan.pay');
});

Route::get('/', function () {
    $kasMasuk = KasMasuk::sum('jumlah');
    $kasKeluar = KasKeluar::sum('jumlah');
    $saldo = $kasMasuk - $kasKeluar;

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

Route::get('/login', function () {
    return view('auth.login-regis'); // Nama file baru lu
})->name('login');

Route::get('/register', function () {
    return view('auth.login-regis'); // Arahkan ke file yang sama
})->name('register');



require __DIR__ . '/auth.php';
