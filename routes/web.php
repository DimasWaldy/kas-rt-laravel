<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\KasMasuk;
use App\Models\KasKeluar;
use App\Models\User;
use App\Http\Controllers\KasMasukController;
use App\Http\Controllers\KasKeluarController;

Route::get('/dashboard', function () {

    $kasMasuk = KasMasuk::sum('jumlah');
    $kasKeluar = KasKeluar::sum('jumlah');

    $tanggal = KasMasuk::pluck('tanggal');
    $dataMasuk = KasMasuk::pluck('jumlah');
    $dataKeluar = KasKeluar::pluck('jumlah');

    // 🔥 TAMBAH INI
    $leaderboard = KasMasuk::selectRaw('user_id, SUM(jumlah) as total')
        ->groupBy('user_id')
        ->orderByDesc('total')
        ->with('user')
        ->limit(5)
        ->get();

    return view('dashboard', compact(
        'kasMasuk',
        'kasKeluar',
        'tanggal',
        'dataMasuk',
        'dataKeluar',
        'leaderboard' // 🔥 WAJIB ADA
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/admin', function () {
    if (auth()->user()->role !== 'admin') {
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

    Route::middleware(['auth'])->group(function () {

        // Rute utama (Penting: harus ada ->name('kas-keluar.index'))
        Route::get('/kas-keluar', [KasKeluarController::class, 'index'])->name('kas-keluar.index');

        // Rute Create (Tambah ->name juga biar aman)
        Route::get('/kas-keluar/create', function () {
            if (auth()->user()->role !== 'admin') {
                return redirect()->route('kas-keluar.index')->with('error', 'Hanya untuk RT/Bendahara ⛔');
            }
            return app(KasKeluarController::class)->create();
        })->name('kas-keluar.create');

        // Rute Store
        Route::post('/kas-keluar/store', function () {
            if (auth()->user()->role !== 'admin') {
                return redirect()->route('kas-keluar.index')->with('error', 'Hanya untuk RT/Bendahara ⛔');
            }
            return app(KasKeluarController::class)->store(request());
        })->name('kas-keluar.store');
    });

    Route::post('/kas-keluar/store', function () {
        if (auth()->user()->role !== 'admin') {
            return redirect('/kas-keluar')
                ->with('error', 'Fitur ini hanya untuk RT/Bendahara ⛔');
        }
        return app(KasKeluarController::class)->store(request());
    });
});

Route::get('/', function () {

    $kasMasuk = KasMasuk::sum('jumlah');
    $kasKeluar = KasKeluar::sum('jumlah');

    $saldo = $kasMasuk - $kasKeluar;

    $recentMasuk = KasMasuk::latest()->take(3)->get();
    $recentKeluar = KasKeluar::latest()->take(3)->get();

    // 🔥 LEADERBOARD
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
        'recentMasuk',
        'recentKeluar',
        'leaderboard' // 🔥 INI PENTING
    ));
});

Route::get('/login', function () {
    return view('auth.login-regis'); // Nama file baru lu
})->name('login');

Route::get('/register', function () {
    return view('auth.login-regis'); // Arahkan ke file yang sama
})->name('register');



require __DIR__ . '/auth.php';
