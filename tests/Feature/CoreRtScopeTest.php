<?php

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $rw = Rw::create([
        'name' => 'RW Test',
        'is_active' => true,
    ]);

    $this->rtSatu = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rtDua = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $this->bendaharaRtSatu = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $wargaRoleId = Role::where('name', 'warga')->value('id');

    $this->wargaRtSatu = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtSatu->id,
        'is_kepala_keluarga' => true,
    ]);

    $this->wargaRtDua = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtDua->id,
        'is_kepala_keluarga' => true,
    ]);
});

test('bendahara rt only sees and verifies bills from their own rt', function () {
    $tagihanRtSatu = Tagihan::create([
        'user_id' => $this->wargaRtSatu->id,
        'rt_id' => $this->rtSatu->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Tagihan RT Satu',
        'total' => 50000,
        'status' => 'pending_offline',
        'payment_method' => 'offline',
    ]);

    $tagihanRtDua = Tagihan::create([
        'user_id' => $this->wargaRtDua->id,
        'rt_id' => $this->rtDua->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Tagihan RT Dua',
        'total' => 70000,
        'status' => 'pending_offline',
        'payment_method' => 'offline',
    ]);

    $this->actingAs($this->bendaharaRtSatu)
        ->get(route('tagihan.admin'))
        ->assertOk()
        ->assertSee('Tagihan RT Satu')
        ->assertDontSee('Tagihan RT Dua');

    $this->actingAs($this->bendaharaRtSatu)
        ->post(route('tagihan.confirm'), [
            'tagihan_id' => $tagihanRtDua->id,
            'status' => 'lunas',
        ])
        ->assertNotFound();

    $this->actingAs($this->bendaharaRtSatu)
        ->post(route('tagihan.confirm'), [
            'tagihan_id' => $tagihanRtSatu->id,
            'status' => 'lunas',
        ])
        ->assertRedirect(route('tagihan.admin'));

    $kasMasuk = KasMasuk::where('tagihan_id', $tagihanRtSatu->id)->firstOrFail();

    expect($kasMasuk->rt_id)->toBe($this->rtSatu->id);
    expect($tagihanRtDua->fresh()->status)->toBe('pending_offline');
});

test('bendahara rt cash report excludes transactions from other rt', function () {
    KasMasuk::create([
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Kas Masuk RT Satu',
        'jumlah' => 100000,
        'tanggal' => now()->toDateString(),
    ]);

    KasMasuk::create([
        'rt_id' => $this->rtDua->id,
        'keterangan' => 'Kas Masuk RT Dua',
        'jumlah' => 900000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Kas Keluar RT Satu',
        'jumlah' => 25000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'rt_id' => $this->rtDua->id,
        'keterangan' => 'Kas Keluar RT Dua',
        'jumlah' => 500000,
        'tanggal' => now()->toDateString(),
    ]);

    $this->actingAs($this->bendaharaRtSatu)
        ->get(route('laporan-kas.index', [
            'tanggal_mulai' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('totalMasuk', 100000)
        ->assertViewHas('totalKeluar', 25000)
        ->assertSee('Kas Masuk RT Satu')
        ->assertDontSee('Kas Masuk RT Dua')
        ->assertDontSee('Kas Keluar RT Dua');
});

test('bendahara rt dashboard totals only include their own rt', function () {
    KasMasuk::create([
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Pemasukan RT Satu',
        'jumlah' => 125000,
        'tanggal' => now()->toDateString(),
    ]);

    KasMasuk::create([
        'rt_id' => $this->rtDua->id,
        'keterangan' => 'Pemasukan RT Dua',
        'jumlah' => 875000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Pengeluaran RT Satu',
        'jumlah' => 20000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'rt_id' => $this->rtDua->id,
        'keterangan' => 'Pengeluaran RT Dua',
        'jumlah' => 400000,
        'tanggal' => now()->toDateString(),
    ]);

    $this->actingAs($this->bendaharaRtSatu)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('kasMasuk', 125000)
        ->assertViewHas('kasKeluar', 20000);
});
