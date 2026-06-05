<?php

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Role;
use App\Models\Tagihan;
use App\Models\User;

function financeUserForLaporan(): User
{
    $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);

    return User::factory()->create(['role_id' => $role->id]);
}

test('finance user can view formal cash report with opening and ending balance', function () {
    $admin = financeUserForLaporan();
    $residentRole = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);
    $resident = User::factory()->create(['role_id' => $residentRole->id]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan' => 5,
        'tahun' => 2026,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Iuran Kebersihan & Keamanan',
        'total' => 50000,
        'status' => 'lunas',
    ]);

    KasMasuk::create([
        'user_id' => $resident->id,
        'keterangan' => 'Saldo awal kas RT',
        'jumlah' => 100000,
        'tanggal' => '2026-04-30',
    ]);

    KasKeluar::create([
        'keterangan' => 'Perbaikan lampu jalan',
        'jumlah' => 25000,
        'tanggal' => '2026-04-30',
    ]);

    KasMasuk::create([
        'user_id' => $resident->id,
        'tagihan_id' => $tagihan->id,
        'keterangan' => 'Pembayaran Iuran Mei 2026',
        'jumlah' => 50000,
        'tanggal' => '2026-05-10',
    ]);

    KasKeluar::create([
        'keterangan' => 'Pembelian alat kebersihan',
        'jumlah' => 15000,
        'tanggal' => '2026-05-12',
    ]);

    $response = $this->actingAs($admin)->get(route('laporan-kas.index', [
        'tanggal_mulai' => '2026-05-01',
        'tanggal_selesai' => '2026-05-31',
    ]));

    $response->assertOk();
    $response->assertViewHas('saldoAwal', 75000);
    $response->assertViewHas('totalMasuk', 50000);
    $response->assertViewHas('totalKeluar', 15000);
    $response->assertViewHas('saldoAkhir', 110000);
    $response->assertSee('Pembayaran Iuran');
    $response->assertSee('Operasional Kebersihan');
});

test('cash report can be filtered by category', function () {
    $admin = financeUserForLaporan();

    KasMasuk::create([
        'keterangan' => 'Donasi warga',
        'jumlah' => 80000,
        'tanggal' => '2026-05-10',
    ]);

    KasKeluar::create([
        'keterangan' => 'Konsumsi rapat warga',
        'jumlah' => 30000,
        'tanggal' => '2026-05-11',
    ]);

    $response = $this->actingAs($admin)->get(route('laporan-kas.index', [
        'tanggal_mulai' => '2026-05-01',
        'tanggal_selesai' => '2026-05-31',
        'kategori' => 'Donasi/Bantuan',
    ]));

    $response->assertOk();
    $response->assertViewHas('totalMasuk', 80000);
    $response->assertViewHas('totalKeluar', 0);
    $response->assertSee('Donasi/Bantuan');
});
