<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoUtsController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\BankSampahController;
use App\Http\Controllers\FasilitasController;
use App\Http\Controllers\HadiahSampahController;
use App\Http\Controllers\IuranBulananController;
use App\Http\Controllers\IuranKhususController;
use App\Http\Controllers\KeamananController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\KasKeluarController;
use App\Http\Controllers\KasMasukController;
use App\Http\Controllers\LaporanKasController;
use App\Http\Controllers\PeminjamanAsetController;
use App\Http\Controllers\PenarikanSampahController;
use App\Http\Controllers\PenjualanSampahController;
use App\Http\Controllers\ProdukUmkmController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PengaduanFasilitasController;
use App\Http\Controllers\SetoranSampahController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\SuratController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/notifications/{id}/read', function (string $id) {
        Auth::user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    })->name('notifications.read');

    Route::post('/notifications/read-all', function () {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    })->name('notifications.read-all');
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

        Route::get('/iuran-khusus', [IuranKhususController::class, 'index'])->name('iuran-khusus.index');
        Route::get('/iuran-khusus/create', [IuranKhususController::class, 'create'])->name('iuran-khusus.create');
        Route::post('/iuran-khusus', [IuranKhususController::class, 'store'])->name('iuran-khusus.store');
        Route::get('/iuran-khusus/{iuranKhusus}', [IuranKhususController::class, 'show'])->name('iuran-khusus.show');
        Route::patch('/iuran-khusus/tagihan/{tagihan}/kecualikan', [IuranKhususController::class, 'kecualikan'])->name('iuran-khusus.kecualikan');
        Route::patch('/iuran-khusus/tagihan/{tagihan}/batal-kecualikan', [IuranKhususController::class, 'batalKecualikan'])->name('iuran-khusus.batal-kecualikan');

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
        Route::get('/kegiatan/{kegiatan}/dokumentasi', [KegiatanController::class, 'dokumentasi'])->name('kegiatan.dokumentasi');
        Route::post('/kegiatan/{kegiatan}/hadir', [KegiatanController::class, 'konfirmasiHadir'])->name('kegiatan.hadir');
    });

    Route::middleware('permission:view-bank-sampah')->group(function () {
        Route::get('/bank-sampah', [BankSampahController::class, 'index'])
            ->name('bank-sampah.index');
        Route::get('/bank-sampah/hadiah', [HadiahSampahController::class, 'index'])
            ->name('hadiah-sampah.index');
        Route::get('/bank-sampah/hadiah/{hadiah}/foto', [HadiahSampahController::class, 'foto'])
            ->name('hadiah-sampah.foto');
    });

    Route::middleware('permission:setor-sampah')->group(function () {
        Route::get('/bank-sampah/setor', [SetoranSampahController::class, 'index'])
            ->name('setoran-sampah.index');
        Route::get('/bank-sampah/setor/create', [SetoranSampahController::class, 'create'])
            ->name('setoran-sampah.create');
        Route::post('/bank-sampah/setor', [SetoranSampahController::class, 'store'])
            ->name('setoran-sampah.store');
        Route::get('/bank-sampah/setor/{setoran}', [SetoranSampahController::class, 'show'])
            ->name('setoran-sampah.show');
        Route::get('/bank-sampah/setor/{setoran}/foto-bukti', [SetoranSampahController::class, 'fotoBukti'])
            ->name('setoran-sampah.foto-bukti');
        Route::get('/bank-sampah/tarik/create', [PenarikanSampahController::class, 'create'])
            ->name('penarikan-sampah.create');
        Route::post('/bank-sampah/tarik', [PenarikanSampahController::class, 'store'])
            ->name('penarikan-sampah.store');
        Route::post('/bank-sampah/hadiah/{hadiah}/tukar', [HadiahSampahController::class, 'tukar'])
            ->name('hadiah-sampah.tukar');
    });

    Route::middleware('permission:manage-bank-sampah')->group(function () {
        Route::get('/bank-sampah/tarik', [PenarikanSampahController::class, 'index'])
            ->name('penarikan-sampah.index');
        Route::patch('/bank-sampah/setor/{setoran}/verifikasi', [SetoranSampahController::class, 'verifikasi'])
            ->name('setoran-sampah.verifikasi');
        Route::patch('/bank-sampah/setor/{setoran}/tolak', [SetoranSampahController::class, 'tolak'])
            ->name('setoran-sampah.tolak');
        Route::patch('/bank-sampah/tarik/{penarikan}/konfirmasi', [PenarikanSampahController::class, 'konfirmasi'])
            ->name('penarikan-sampah.konfirmasi');
        Route::get('/bank-sampah/hadiah/create', [HadiahSampahController::class, 'create'])
            ->name('hadiah-sampah.create');
        Route::post('/bank-sampah/hadiah', [HadiahSampahController::class, 'store'])
            ->name('hadiah-sampah.store');
        Route::patch('/bank-sampah/hadiah/tukar/{penukaran}/konfirmasi', [HadiahSampahController::class, 'konfirmasiTukar'])
            ->name('hadiah-sampah.konfirmasi-tukar');
        Route::get('/bank-sampah/penjualan', [PenjualanSampahController::class, 'index'])
            ->name('penjualan-sampah.index');
        Route::get('/bank-sampah/penjualan/create', [PenjualanSampahController::class, 'create'])
            ->name('penjualan-sampah.create');
        Route::post('/bank-sampah/penjualan', [PenjualanSampahController::class, 'store'])
            ->name('penjualan-sampah.store');
    });

    Route::middleware('permission:manage-fasilitas')->group(function () {
        Route::get('/fasilitas/create', [FasilitasController::class, 'create'])
            ->name('fasilitas.create');
        Route::post('/fasilitas', [FasilitasController::class, 'store'])
            ->name('fasilitas.store');
        Route::get('/fasilitas/{fasilitas}/edit', [FasilitasController::class, 'edit'])
            ->name('fasilitas.edit');
        Route::put('/fasilitas/{fasilitas}', [FasilitasController::class, 'update'])
            ->name('fasilitas.update');
        Route::delete('/fasilitas/{fasilitas}', [FasilitasController::class, 'destroy'])
            ->name('fasilitas.destroy');

        Route::get('/keamanan', [KeamananController::class, 'index'])
            ->name('keamanan.index');
        Route::get('/keamanan/shift/create', [KeamananController::class, 'createShift'])
            ->name('keamanan.shift.create');
        Route::post('/keamanan/shift', [KeamananController::class, 'storeShift'])
            ->name('keamanan.shift.store');
        Route::get('/keamanan/shift/{shift}', [KeamananController::class, 'showShift'])
            ->name('keamanan.shift.show');
        Route::post('/keamanan/shift/{shift}/patroli', [KeamananController::class, 'storePatroli'])
            ->name('keamanan.patroli.store');
    });

    Route::middleware('permission:view-fasilitas')->group(function () {
        Route::get('/fasilitas', [FasilitasController::class, 'index'])
            ->name('fasilitas.index');
        Route::get('/fasilitas/{fasilitas}', [FasilitasController::class, 'show'])
            ->name('fasilitas.show');
        Route::get('/fasilitas/{fasilitas}/foto', [FasilitasController::class, 'foto'])
            ->name('fasilitas.foto');
    });

    Route::middleware('permission:lapor-fasilitas')->group(function () {
        Route::get('/pengaduan-fasilitas', [PengaduanFasilitasController::class, 'index'])
            ->name('pengaduan-fasilitas.index');
        Route::get('/pengaduan-fasilitas/create', [PengaduanFasilitasController::class, 'create'])
            ->name('pengaduan-fasilitas.create');
        Route::post('/pengaduan-fasilitas', [PengaduanFasilitasController::class, 'store'])
            ->name('pengaduan-fasilitas.store');
        Route::get('/pengaduan-fasilitas/{pengaduan}', [PengaduanFasilitasController::class, 'show'])
            ->name('pengaduan-fasilitas.show');
        Route::get('/pengaduan-fasilitas/{pengaduan}/foto', [PengaduanFasilitasController::class, 'foto'])
            ->name('pengaduan-fasilitas.foto');
    });

    Route::middleware('permission:manage-fasilitas')->group(function () {
        Route::patch('/pengaduan-fasilitas/{pengaduan}/tindak-lanjut', [PengaduanFasilitasController::class, 'tindakLanjut'])
            ->name('pengaduan-fasilitas.tindak-lanjut');
        Route::patch('/pengaduan-fasilitas/{pengaduan}/selesai', [PengaduanFasilitasController::class, 'selesaikan'])
            ->name('pengaduan-fasilitas.selesai');
        Route::patch('/pengaduan-fasilitas/{pengaduan}/tolak', [PengaduanFasilitasController::class, 'tolak'])
            ->name('pengaduan-fasilitas.tolak');
    });

    // Static UMKM paths must be registered before /umkm/{umkm}.
    Route::middleware('permission:daftar-umkm')->group(function () {
        Route::get('/umkm-saya', [UmkmController::class, 'myUmkm'])
            ->name('umkm.saya');
        Route::get('/umkm/create', [UmkmController::class, 'create'])
            ->name('umkm.create');
        Route::post('/umkm', [UmkmController::class, 'store'])
            ->name('umkm.store');
    });

    Route::middleware('permission:view-umkm')->group(function () {
        Route::get('/umkm', [UmkmController::class, 'index'])
            ->name('umkm.index');
        Route::get('/produk-umkm/{produk}/foto', [ProdukUmkmController::class, 'foto'])
            ->name('produk-umkm.foto');
        Route::get('/umkm/{umkm}', [UmkmController::class, 'show'])
            ->name('umkm.show');
        Route::get('/umkm/{umkm}/foto', [UmkmController::class, 'foto'])
            ->name('umkm.foto');
    });

    Route::middleware('permission:daftar-umkm')->group(function () {
        Route::get('/umkm/{umkm}/edit', [UmkmController::class, 'edit'])
            ->name('umkm.edit');
        Route::put('/umkm/{umkm}', [UmkmController::class, 'update'])
            ->name('umkm.update');
        Route::patch('/umkm/{umkm}/nonaktifkan', [UmkmController::class, 'nonaktifkan'])
            ->name('umkm.nonaktifkan');
        Route::patch('/umkm/{umkm}/aktifkan-kembali', [UmkmController::class, 'aktifkanKembali'])
            ->name('umkm.aktifkan-kembali');

        Route::post('/umkm/{umkm}/produk', [ProdukUmkmController::class, 'store'])
            ->name('produk-umkm.store');
        Route::put('/produk-umkm/{produk}', [ProdukUmkmController::class, 'update'])
            ->name('produk-umkm.update');
        Route::delete('/produk-umkm/{produk}', [ProdukUmkmController::class, 'destroy'])
            ->name('produk-umkm.destroy');
        Route::patch('/produk-umkm/{produk}/toggle', [ProdukUmkmController::class, 'toggleAvailability'])
            ->name('produk-umkm.toggle');
    });

    Route::middleware('permission:manage-umkm')->group(function () {
        Route::patch('/umkm/{umkm}/approve', [UmkmController::class, 'approve'])
            ->name('umkm.approve');
        Route::patch('/umkm/{umkm}/reject', [UmkmController::class, 'reject'])
            ->name('umkm.reject');
    });

    Route::middleware('permission:view-aset')->group(function () {
        Route::get('/aset', [AsetController::class, 'index'])
            ->name('aset.index');
    });

    Route::middleware('permission:view-aset-rw')->group(function () {
        Route::get('/aset-rw', [AsetController::class, 'index'])
            ->defaults('scope', 'rw')
            ->name('aset-rw.index');
    });

    Route::middleware('permission:manage-aset')->group(function () {
        Route::get('/aset/create', [AsetController::class, 'create'])
            ->name('aset.create');
        Route::post('/aset', [AsetController::class, 'store'])
            ->name('aset.store');
    });

    Route::middleware('permission:manage-aset-rw')->group(function () {
        Route::get('/aset-rw/create', [AsetController::class, 'create'])
            ->defaults('scope', 'rw')
            ->name('aset-rw.create');
    });

    Route::middleware('permission:view-aset')->group(function () {
        Route::get('/aset/{aset}', [AsetController::class, 'show'])
            ->name('aset.show');
        Route::get('/aset/{aset}/foto', [AsetController::class, 'foto'])
            ->name('aset.foto');
    });

    Route::middleware('permission:manage-aset')->group(function () {
        Route::get('/aset/{aset}/edit', [AsetController::class, 'edit'])
            ->name('aset.edit');
        Route::put('/aset/{aset}', [AsetController::class, 'update'])
            ->name('aset.update');
        Route::delete('/aset/{aset}', [AsetController::class, 'destroy'])
            ->name('aset.destroy');
    });

    Route::middleware('permission:view-aset-rw')->group(function () {
        Route::get('/aset-rw/{aset}', [AsetController::class, 'show'])
            ->name('aset-rw.show');
        Route::get('/aset-rw/{aset}/foto', [AsetController::class, 'foto'])
            ->name('aset-rw.foto');
    });

    Route::middleware('permission:manage-aset-rw')->group(function () {
        Route::post('/aset-rw', [AsetController::class, 'store'])
            ->defaults('scope', 'rw')
            ->name('aset-rw.store');
        Route::get('/aset-rw/{aset}/edit', [AsetController::class, 'edit'])
            ->name('aset-rw.edit');
        Route::put('/aset-rw/{aset}', [AsetController::class, 'update'])
            ->name('aset-rw.update');
        Route::delete('/aset-rw/{aset}', [AsetController::class, 'destroy'])
            ->name('aset-rw.destroy');
    });

    Route::middleware('permission:pinjam-aset')->group(function () {
        Route::get('/peminjaman-aset', [PeminjamanAsetController::class, 'index'])
            ->name('peminjaman-aset.index');
        Route::get('/peminjaman-aset/create', [PeminjamanAsetController::class, 'create'])
            ->name('peminjaman-aset.create');
        Route::post('/peminjaman-aset', [PeminjamanAsetController::class, 'store'])
            ->name('peminjaman-aset.store');
        Route::get('/peminjaman-aset/{peminjamanAset}', [PeminjamanAsetController::class, 'show'])
            ->name('peminjaman-aset.show');
    });

    Route::middleware('permission:pinjam-aset-rw')->group(function () {
        Route::get('/peminjaman-aset-rw', [PeminjamanAsetController::class, 'index'])
            ->defaults('scope', 'rw')
            ->name('peminjaman-aset-rw.index');
        Route::get('/peminjaman-aset-rw/create', [PeminjamanAsetController::class, 'create'])
            ->defaults('scope', 'rw')
            ->name('peminjaman-aset-rw.create');
        Route::post('/peminjaman-aset-rw', [PeminjamanAsetController::class, 'store'])
            ->defaults('scope', 'rw')
            ->name('peminjaman-aset-rw.store');
        Route::get('/peminjaman-aset-rw/{peminjamanAset}', [PeminjamanAsetController::class, 'show'])
            ->name('peminjaman-aset-rw.show');
    });

    Route::middleware('permission:manage-aset')->group(function () {
        Route::patch('/peminjaman-aset/{peminjamanAset}/setujui', [PeminjamanAsetController::class, 'setujui'])
            ->name('peminjaman-aset.setujui');
        Route::patch('/peminjaman-aset/{peminjamanAset}/tolak', [PeminjamanAsetController::class, 'tolak'])
            ->name('peminjaman-aset.tolak');
        Route::patch('/peminjaman-aset/{peminjamanAset}/dipinjam', [PeminjamanAsetController::class, 'konfirmasiDipinjam'])
            ->name('peminjaman-aset.dipinjam');
        Route::patch('/peminjaman-aset/{peminjamanAset}/kembali', [PeminjamanAsetController::class, 'konfirmasiKembali'])
            ->name('peminjaman-aset.kembali');
    });

    Route::middleware('permission:manage-aset-rw')->group(function () {
        Route::patch('/peminjaman-aset-rw/{peminjamanAset}/setujui', [PeminjamanAsetController::class, 'setujui'])
            ->name('peminjaman-aset-rw.setujui');
        Route::patch('/peminjaman-aset-rw/{peminjamanAset}/tolak', [PeminjamanAsetController::class, 'tolak'])
            ->name('peminjaman-aset-rw.tolak');
        Route::patch('/peminjaman-aset-rw/{peminjamanAset}/dipinjam', [PeminjamanAsetController::class, 'konfirmasiDipinjam'])
            ->name('peminjaman-aset-rw.dipinjam');
        Route::patch('/peminjaman-aset-rw/{peminjamanAset}/kembali', [PeminjamanAsetController::class, 'konfirmasiKembali'])
            ->name('peminjaman-aset-rw.kembali');
    });
});

Route::get('/', [WelcomeController::class, 'index']);

Route::get('/verifikasi-surat/{code}', [SuratController::class, 'verifyPublic'])->name('surat.verify-public');

require __DIR__ . '/auth.php';
