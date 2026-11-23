<?php

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;

beforeEach(function () {
    $this->rw = Rw::create([
        'name' => '05',
        'description' => 'RW 05',
    ]);

    $this->rt1 = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '01',
        'description' => 'RT 01',
    ]);

    $this->rt2 = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '02',
        'description' => 'RT 02',
    ]);

    // Permissions
    $pViewFinance = Permission::firstOrCreate(['name' => 'view-finance'], ['description' => 'View finance']);
    $pManageFinance = Permission::firstOrCreate(['name' => 'manage-finance'], ['description' => 'Manage finance']);
    $pExportFinance = Permission::firstOrCreate(['name' => 'export-finance'], ['description' => 'Export finance']);

    // Roles
    $this->roleBendahara = Role::firstOrCreate(['name' => 'bendahara_rt'], ['description' => 'Bendahara RT']);
    $this->roleBendahara->permissions()->syncWithoutDetaching([
        $pViewFinance->id, $pManageFinance->id, $pExportFinance->id,
    ]);

    $this->roleWarga = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga RT']);
    $this->roleWarga->permissions()->syncWithoutDetaching([
        $pViewFinance->id,
    ]);

    // Users
    $this->bendahara1 = User::factory()->create([
        'role_id' => $this->roleBendahara->id,
        'rt_id' => $this->rt1->id,
        'status_akun' => 'aktif',
    ]);

    $this->warga1 = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rt1->id,
        'status_akun' => 'aktif',
    ]);

    $this->warga2 = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rt2->id,
        'status_akun' => 'aktif',
    ]);

    // Rumah
    $this->rumah1 = Rumah::create([
        'rt_id' => $this->rt1->id,
        'kode_rumah' => 'A-01',
        'alamat' => 'Jl. Mawar No. 1',
    ]);

    $this->rumah2 = Rumah::create([
        'rt_id' => $this->rt2->id,
        'kode_rumah' => 'B-02',
        'alamat' => 'Jl. Melati No. 2',
    ]);
});

test('bendahara can view laporan kas index and export pdf', function () {
    KasMasuk::create([
        'user_id' => $this->bendahara1->id,
        'rt_id' => $this->rt1->id,
        'keterangan' => 'Donasi Pembangunan Pos RT 01',
        'jumlah' => 1000000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'rt_id' => $this->rt1->id,
        'keterangan' => 'Pembelian Lampu Jalan RT 01',
        'jumlah' => 250000,
        'tanggal' => now()->toDateString(),
    ]);

    // Index
    $indexRes = $this->actingAs($this->bendahara1)->get(route('laporan-kas.index'));
    $indexRes->assertOk();
    $indexRes->assertSee('Donasi Pembangunan Pos RT 01');

    // Export PDF View
    $exportRes = $this->actingAs($this->bendahara1)->get(route('laporan-kas.export'));
    $exportRes->assertOk();
    $exportRes->assertSee('LAPORAN KAS UMUM');
    $exportRes->assertSee('Donasi Pembangunan Pos RT 01');
    $exportRes->assertSee('Pembelian Lampu Jalan RT 01');
});

test('warga without export-finance permission cannot access laporan kas export', function () {
    $response = $this->actingAs($this->warga1)->get(route('laporan-kas.export'));

    $response->assertForbidden();
});

test('laporan kas export respects multi-tenant RT isolation', function () {
    KasMasuk::create([
        'user_id' => $this->bendahara1->id,
        'rt_id' => $this->rt1->id,
        'keterangan' => 'Kas Masuk RT 01 Rahasia',
        'jumlah' => 500000,
        'tanggal' => now()->toDateString(),
    ]);

    KasMasuk::create([
        'user_id' => $this->warga2->id,
        'rt_id' => $this->rt2->id,
        'keterangan' => 'Kas Masuk RT 02 Milik Tetangga',
        'jumlah' => 750000,
        'tanggal' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->bendahara1)->get(route('laporan-kas.export'));
    $response->assertOk();
    $response->assertSee('Kas Masuk RT 01 Rahasia');
    $response->assertDontSee('Kas Masuk RT 02 Milik Tetangga');
});

test('bendahara can view laporan iuran index with statistics', function () {
    Tagihan::create([
        'user_id' => $this->warga1->id,
        'rumah_id' => $this->rumah1->id,
        'rt_id' => $this->rt1->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'judul' => 'Iuran Kebersihan & Keamanan',
        'total' => 50000,
        'status' => 'lunas',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->bendahara1)->get(route('laporan-iuran.index', [
        'bulan' => now()->month,
        'tahun' => now()->year,
    ]));

    $response->assertOk();
    $response->assertSee('Laporan Iuran Warga');
    $response->assertSee('Iuran Kebersihan &amp; Keamanan', false);
    $response->assertSee('50.000');
});

test('warga without export-finance permission cannot access laporan iuran export', function () {
    $response = $this->actingAs($this->warga1)->get(route('laporan-iuran.export'));

    $response->assertForbidden();
});

test('bendahara can export laporan iuran warga with accurate breakdown and RT isolation', function () {
    // Tagihan RT 01 (Lunas)
    Tagihan::create([
        'user_id' => $this->warga1->id,
        'rumah_id' => $this->rumah1->id,
        'rt_id' => $this->rt1->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'judul' => 'Iuran Wajib RT 01',
        'total' => 30000,
        'status' => 'lunas',
        'paid_at' => now(),
        'payment_method' => 'transfer',
    ]);

    // Tagihan RT 02 (Milik RT lain)
    Tagihan::create([
        'user_id' => $this->warga2->id,
        'rumah_id' => $this->rumah2->id,
        'rt_id' => $this->rt2->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'judul' => 'Iuran Wajib RT 02',
        'total' => 45000,
        'status' => 'belum_bayar',
    ]);

    $response = $this->actingAs($this->bendahara1)->get(route('laporan-iuran.export', [
        'bulan' => now()->month,
        'tahun' => now()->year,
    ]));

    $response->assertOk();
    $response->assertSee('LAPORAN REKAPITULASI PENERIMAAN IURAN WARGA');
    $response->assertSee('Iuran Wajib RT 01');
    $response->assertSee('A-01');
    $response->assertDontSee('Iuran Wajib RT 02');
    $response->assertDontSee('B-02');
});
