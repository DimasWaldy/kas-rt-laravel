<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoUtsController;
use App\Http\Controllers\IuranBulananController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\KasKeluarController;
use App\Http\Controllers\KasMasukController;
use App\Http\Controllers\LaporanKasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\SuratController;
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
        Route::get('/kas-keluar/{kasKeluar}/bukti', [KasKeluarController::class, 'bukti'])->name('kas-keluar.bukti');

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
        Route::get('/admin/demo-uts', [DemoUtsController::class, 'index'])->name('demo-uts.index');
    });

    Route::get('/admin/laporan-kas', [LaporanKasController::class, 'index'])
        ->middleware('permission:view-finance')
        ->name('laporan-kas.index');

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
    Route::get('/tagihan/{tagihan}/bukti', [TagihanController::class, 'bukti'])->name('tagihan.bukti');

    Route::get('/pengaduan/{pengaduan}/foto', [\App\Http\Controllers\PengaduanController::class, 'foto'])->name('pengaduan.foto');
    Route::resource('pengaduan', \App\Http\Controllers\PengaduanController::class)->except(['edit', 'update']);
    Route::patch('/pengaduan/{pengaduan}/status', [\App\Http\Controllers\PengaduanController::class, 'updateStatus'])
        ->middleware('permission:manage-pengaduan')
        ->name('pengaduan.status');

    Route::get('/surat', [SuratController::class, 'index'])
        ->middleware('permission:view-surat')
        ->name('surat.index');
    Route::get('/surat/create', [SuratController::class, 'create'])
        ->middleware('permission:submit-surat')
        ->name('surat.create');
    Route::post('/surat', [SuratController::class, 'store'])
        ->middleware('permission:submit-surat')
        ->name('surat.store');
    Route::get('/surat/{surat}', [SuratController::class, 'show'])
        ->middleware('permission:view-surat')
        ->name('surat.show');
    Route::get('/surat/{surat}/lampiran/{attachment}', [SuratController::class, 'attachment'])->name('surat.attachment');
    Route::patch('/surat/{surat}/verifikasi-rt', [SuratController::class, 'verifyRt'])->name('surat.verify-rt');
    Route::patch('/surat/{surat}/setujui-rt', [SuratController::class, 'approveRt'])->name('surat.approve-rt');
    Route::patch('/surat/{surat}/verifikasi-rw', [SuratController::class, 'verifyRw'])->name('surat.verify-rw');
    Route::patch('/surat/{surat}/setujui-rw', [SuratController::class, 'approveRw'])->name('surat.approve-rw');
    Route::patch('/surat/{surat}/tolak', [SuratController::class, 'reject'])->name('surat.reject');
    Route::get('/surat/{surat}/cetak', [SuratController::class, 'print'])->name('surat.print');

    Route::middleware('permission:manage-kegiatan')->group(function () {
        Route::get('/kegiatan/create', [KegiatanController::class, 'create'])->name('kegiatan.create');
        Route::post('/kegiatan', [KegiatanController::class, 'store'])->name('kegiatan.store');
        Route::get('/kegiatan/{kegiatan}/edit', [KegiatanController::class, 'edit'])->name('kegiatan.edit');
        Route::put('/kegiatan/{kegiatan}', [KegiatanController::class, 'update'])->name('kegiatan.update');
        Route::delete('/kegiatan/{kegiatan}', [KegiatanController::class, 'destroy'])->name('kegiatan.destroy');
        Route::patch('/kegiatan/{kegiatan}/batalkan', [KegiatanController::class, 'batalkan'])->name('kegiatan.batalkan');
    });

    Route::middleware('permission:view-kegiatan')->group(function () {
        Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
        Route::get('/kegiatan/{kegiatan}', [KegiatanController::class, 'show'])->name('kegiatan.show');
        Route::get('/kegiatan/{kegiatan}/foto', [KegiatanController::class, 'foto'])->name('kegiatan.foto');
        Route::post('/kegiatan/{kegiatan}/hadir', [KegiatanController::class, 'konfirmasiHadir'])->name('kegiatan.hadir');
    });
});

Route::get('/', [WelcomeController::class, 'index']);

Route::get('/verifikasi-surat/{code}', [SuratController::class, 'verifyPublic'])->name('surat.verify-public');

require __DIR__ . '/auth.php';
