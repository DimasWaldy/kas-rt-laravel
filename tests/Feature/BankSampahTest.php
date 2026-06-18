<?php

use App\Models\HadiahSampah;
use App\Models\JenisSampah;
use App\Models\PenarikanSampah;
use App\Models\PenjualanSampah;
use App\Models\PenukaranHadiah;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\SaldoSampah;
use App\Models\SetoranSampah;
use App\Models\TransaksiSampah;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Bank Sampah',
        'address' => 'Jl. Hijau No. 1',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rt = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rwLain = Rw::create([
        'name' => 'RW Lain',
        'address' => 'Jl. Beda No. 2',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rtLain = Rt::create([
        'rw_id' => $this->rwLain->id,
        'name' => 'RT 99',
        'is_active' => true,
    ]);

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->petugas = User::factory()->create([
        'role_id' => $roleId('ketua_rw'),
        'rt_id' => null,
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rt->id,
    ]);

    $this->wargaLain = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtLain->id,
    ]);

    $this->petugasLain = User::factory()->create([
        'role_id' => $roleId('ketua_rw'),
        'rt_id' => $this->rtLain->id,
    ]);

    $this->jenis = JenisSampah::create([
        'rw_id' => $this->rw->id,
        'nama' => 'Plastik PET',
        'satuan' => 'kg',
        'harga_per_satuan' => 2500,
        'is_active' => true,
    ]);

    $this->jenisLain = JenisSampah::create([
        'rw_id' => $this->rwLain->id,
        'nama' => 'Kertas',
        'satuan' => 'kg',
        'harga_per_satuan' => 1500,
        'is_active' => true,
    ]);

    $this->buatSetoran = function (array $attributes = []): SetoranSampah {
        return SetoranSampah::create(array_merge([
            'rw_id' => $this->rw->id,
            'warga_id' => $this->warga->id,
            'jenis_sampah_id' => $this->jenis->id,
            'estimasi_berat' => 2.5,
            'nilai' => 0,
            'status' => 'menunggu',
            'tanggal_setor' => now()->toDateString(),
        ], $attributes));
    };

    $this->beriSaldo = function (int $jumlah = 20000): SaldoSampah {
        return SaldoSampah::updateOrCreate(
            ['warga_id' => $this->warga->id],
            [
                'rw_id' => $this->rw->id,
                'saldo' => $jumlah,
                'total_setor' => $jumlah,
                'total_tarik' => 0,
                'total_tukar' => 0,
            ]
        );
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('warga dapat mengajukan setoran sampah dan saldo belum berubah', function () {
    $response = $this->actingAs($this->warga)
        ->post(route('setoran-sampah.store'), [
            'jenis_sampah_id' => $this->jenis->id,
            'estimasi_berat' => 3.25,
            'tanggal_setor' => now()->toDateString(),
            'catatan_warga' => 'Sampah sudah dipilah.',
        ]);

    $response->assertRedirect(route('setoran-sampah.index'));

    $this->assertDatabaseHas('setoran_sampahs', [
        'rw_id' => $this->rw->id,
        'warga_id' => $this->warga->id,
        'jenis_sampah_id' => $this->jenis->id,
        'status' => 'menunggu',
        'nilai' => 0,
    ]);

    expect(SaldoSampah::where('warga_id', $this->warga->id)->exists())->toBeFalse();
});

test('petugas verifikasi setoran dan saldo warga bertambah', function () {
    $setoran = ($this->buatSetoran)();

    $this->actingAs($this->petugas)
        ->patch(route('setoran-sampah.verifikasi', $setoran), [
            'berat_aktual' => 4,
            'catatan_petugas' => 'Berat sesuai timbangan.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $setoran->refresh();
    $saldo = SaldoSampah::where('warga_id', $this->warga->id)->firstOrFail();

    expect($setoran->status)->toBe('diverifikasi')
        ->and($setoran->nilai)->toBe(10000)
        ->and($saldo->saldo)->toBe(10000)
        ->and($saldo->total_setor)->toBe(10000);

    $this->assertDatabaseHas('transaksi_sampahs', [
        'warga_id' => $this->warga->id,
        'rw_id' => $this->rw->id,
        'tipe' => 'kredit',
        'kategori' => 'setoran',
        'jumlah' => 10000,
        'referensi_id' => $setoran->id,
        'referensi_type' => SetoranSampah::class,
    ]);
});

test('petugas tolak setoran dan saldo tidak berubah', function () {
    $setoran = ($this->buatSetoran)();

    $this->actingAs($this->petugas)
        ->patch(route('setoran-sampah.tolak', $setoran), [
            'catatan_petugas' => 'Sampah tidak sesuai kriteria.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($setoran->fresh()->status)->toBe('ditolak')
        ->and(SaldoSampah::where('warga_id', $this->warga->id)->exists())->toBeFalse()
        ->and(TransaksiSampah::count())->toBe(0);
});

test('warga request penarikan dan petugas konfirmasi saldo berkurang', function () {
    ($this->beriSaldo)(20000);

    $this->actingAs($this->warga)
        ->post(route('penarikan-sampah.store'), [
            'jumlah' => 7000,
            'catatan_warga' => 'Ambil tunai sore.',
        ])
        ->assertRedirect(route('bank-sampah.index'))
        ->assertSessionHas('success');

    $penarikan = PenarikanSampah::firstOrFail();
    expect($penarikan->status)->toBe('menunggu')
        ->and(SaldoSampah::where('warga_id', $this->warga->id)->first()->saldo)->toBe(20000);

    $this->actingAs($this->petugas)
        ->patch(route('penarikan-sampah.konfirmasi', $penarikan))
        ->assertRedirect()
        ->assertSessionHas('success');

    $saldo = SaldoSampah::where('warga_id', $this->warga->id)->firstOrFail();

    expect($penarikan->fresh()->status)->toBe('dibayar')
        ->and($saldo->saldo)->toBe(13000)
        ->and($saldo->total_tarik)->toBe(7000);
});

test('warga tidak bisa tarik melebihi saldo', function () {
    ($this->beriSaldo)(5000);

    $this->actingAs($this->warga)
        ->post(route('penarikan-sampah.store'), [
            'jumlah' => 10000,
        ])
        ->assertSessionHasErrors('jumlah');

    expect(PenarikanSampah::count())->toBe(0)
        ->and(SaldoSampah::where('warga_id', $this->warga->id)->first()->saldo)->toBe(5000);
});

test('warga tukar hadiah dan petugas konfirmasi saldo serta stok berkurang', function () {
    ($this->beriSaldo)(20000);
    $hadiah = HadiahSampah::create([
        'rw_id' => $this->rw->id,
        'nama' => 'Payung',
        'nilai_tukar' => 12000,
        'stok' => 3,
        'is_active' => true,
    ]);

    $this->actingAs($this->warga)
        ->post(route('hadiah-sampah.tukar', $hadiah), [
            'catatan' => 'Ambil saat rapat RW.',
        ])
        ->assertRedirect(route('hadiah-sampah.index'))
        ->assertSessionHas('success');

    $penukaran = PenukaranHadiah::firstOrFail();
    expect($penukaran->status)->toBe('menunggu')
        ->and(SaldoSampah::where('warga_id', $this->warga->id)->first()->saldo)->toBe(20000);

    $this->actingAs($this->petugas)
        ->patch(route('hadiah-sampah.konfirmasi-tukar', $penukaran))
        ->assertRedirect()
        ->assertSessionHas('success');

    $saldo = SaldoSampah::where('warga_id', $this->warga->id)->firstOrFail();

    expect($penukaran->fresh()->status)->toBe('diberikan')
        ->and($saldo->saldo)->toBe(8000)
        ->and($saldo->total_tukar)->toBe(12000)
        ->and($hadiah->fresh()->stok)->toBe(2);
});

test('warga tidak bisa tukar hadiah jika saldo kurang', function () {
    ($this->beriSaldo)(5000);
    $hadiah = HadiahSampah::create([
        'rw_id' => $this->rw->id,
        'nama' => 'Mug',
        'nilai_tukar' => 10000,
        'stok' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($this->warga)
        ->post(route('hadiah-sampah.tukar', $hadiah))
        ->assertSessionHasErrors('hadiah');

    expect(PenukaranHadiah::count())->toBe(0)
        ->and($hadiah->fresh()->stok)->toBe(2);
});

test('dua request verifikasi setoran yang sama hanya menambah saldo sekali', function () {
    $setoran = ($this->buatSetoran)();

    $this->actingAs($this->petugas)
        ->patch(route('setoran-sampah.verifikasi', $setoran), [
            'berat_aktual' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($this->petugas)
        ->patch(route('setoran-sampah.verifikasi', $setoran), [
            'berat_aktual' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $saldo = SaldoSampah::where('warga_id', $this->warga->id)->firstOrFail();

    expect($saldo->saldo)->toBe(5000)
        ->and(TransaksiSampah::where('referensi_id', $setoran->id)->where('referensi_type', SetoranSampah::class)->count())->toBe(1);
});

test('warga rw lain hanya melihat data bank sampah rw sendiri', function () {
    ($this->beriSaldo)(15000);
    SaldoSampah::create([
        'warga_id' => $this->wargaLain->id,
        'rw_id' => $this->rwLain->id,
        'saldo' => 3000,
        'total_setor' => 3000,
        'total_tarik' => 0,
        'total_tukar' => 0,
    ]);

    $this->actingAs($this->wargaLain)
        ->get(route('bank-sampah.index'))
        ->assertOk()
        ->assertSee('Rp 3.000')
        ->assertDontSee('Rp 15.000');
});

test('petugas tidak bisa verifikasi setoran rw lain', function () {
    $setoranRwLain = SetoranSampah::create([
        'rw_id' => $this->rwLain->id,
        'warga_id' => $this->wargaLain->id,
        'jenis_sampah_id' => $this->jenisLain->id,
        'estimasi_berat' => 2,
        'nilai' => 0,
        'status' => 'menunggu',
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->actingAs($this->petugas)
        ->patch(route('setoran-sampah.verifikasi', $setoranRwLain), [
            'berat_aktual' => 2,
        ])
        ->assertForbidden();

    expect($setoranRwLain->fresh()->status)->toBe('menunggu')
        ->and(TransaksiSampah::count())->toBe(0);
});

test('role petugas bank sampah dapat mengelola operasional bank sampah', function () {
    $role = Role::where('name', 'petugas_bank_sampah')->firstOrFail();
    $petugasBankSampah = User::factory()->create([
        'role_id' => $role->id,
        'rt_id' => null,
    ]);
    $setoran = ($this->buatSetoran)();

    expect($petugasBankSampah->hasPermission('manage-bank-sampah'))->toBeTrue()
        ->and($petugasBankSampah->hasPermission('view-bank-sampah'))->toBeTrue()
        ->and($petugasBankSampah->hasPermission('setor-sampah'))->toBeTrue();

    $this->actingAs($petugasBankSampah)
        ->get(route('bank-sampah.index'))
        ->assertOk()
        ->assertSee('Setoran Menunggu Verifikasi');

    $this->actingAs($petugasBankSampah)
        ->patch(route('setoran-sampah.verifikasi', $setoran), [
            'berat_aktual' => 2,
            'catatan_petugas' => 'Diverifikasi petugas bank sampah.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($setoran->fresh()->status)->toBe('diverifikasi')
        ->and(SaldoSampah::where('warga_id', $this->warga->id)->first()->saldo)->toBe(5000);
});

test('petugas bank sampah dapat mencatat penjualan sampah ke pengepul', function () {
    $role = Role::where('name', 'petugas_bank_sampah')->firstOrFail();
    $petugasBankSampah = User::factory()->create([
        'role_id' => $role->id,
        'rt_id' => null,
    ]);

    $this->actingAs($petugasBankSampah)
        ->post(route('penjualan-sampah.store'), [
            'jenis_sampah_id' => $this->jenis->id,
            'tanggal_jual' => now()->toDateString(),
            'berat_total' => 12.5,
            'harga_jual' => 3000,
            'nama_pengepul' => 'CV Hijau Lestari',
            'catatan' => 'Penjualan batch pertama.',
        ])
        ->assertRedirect(route('penjualan-sampah.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('penjualan_sampahs', [
        'rw_id' => $this->rw->id,
        'petugas_id' => $petugasBankSampah->id,
        'jenis_sampah_id' => $this->jenis->id,
        'harga_jual' => 3000,
        'total' => 37500,
        'nama_pengepul' => 'CV Hijau Lestari',
    ]);

    $this->actingAs($petugasBankSampah)
        ->get(route('bank-sampah.index'))
        ->assertOk()
        ->assertSee('Kas Bank Sampah')
        ->assertSee('Rp 37.500');
});

test('warga tidak bisa mencatat penjualan sampah ke pengepul', function () {
    $this->actingAs($this->warga)
        ->post(route('penjualan-sampah.store'), [
            'jenis_sampah_id' => $this->jenis->id,
            'tanggal_jual' => now()->toDateString(),
            'berat_total' => 5,
            'harga_jual' => 2500,
        ])
        ->assertForbidden();

    expect(PenjualanSampah::count())->toBe(0);
});

test('petugas tidak bisa mencatat penjualan untuk jenis sampah rw lain', function () {
    $this->actingAs($this->petugas)
        ->post(route('penjualan-sampah.store'), [
            'jenis_sampah_id' => $this->jenisLain->id,
            'tanggal_jual' => now()->toDateString(),
            'berat_total' => 5,
            'harga_jual' => 2500,
        ])
        ->assertSessionHasErrors('jenis_sampah_id');

    expect(PenjualanSampah::count())->toBe(0);
});
