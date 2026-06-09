<?php

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('admin dashboard exposes actionable finance and billing data', function () {
    Cache::forget('admin.dashboard.stats');
    Cache::forget('admin.dashboard.stats.v2');
    Cache::forget('admin.dashboard.stats.v3');

    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);
    $wargaRole = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $rumah = Rumah::create([
        'kode_rumah' => 'D-01',
        'alamat' => 'Jl. Dashboard No. 1',
        'status' => 'aktif',
    ]);

    $warga = User::factory()->create([
        'role_id' => $wargaRole->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $rumah->update(['penanggung_jawab_id' => $warga->id]);

    Tagihan::create([
        'user_id' => $warga->id,
        'rumah_id' => $rumah->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'billing_group' => 'iuran_rutin',
        'judul' => 'Iuran Kebersihan & Keamanan',
        'total' => 35000,
        'status' => 'pending_transfer',
        'payment_method' => 'transfer',
        'verification_status' => 'menunggu',
    ]);

    Tagihan::create([
        'user_id' => $warga->id,
        'rumah_id' => $rumah->id,
        'bulan' => now()->copy()->subMonth()->month,
        'tahun' => now()->copy()->subMonth()->year,
        'billing_group' => 'iuran_dana_sosial',
        'judul' => 'Dana Sosial',
        'total' => 5000,
        'status' => 'belum_bayar',
    ]);

    KasMasuk::create([
        'user_id' => $warga->id,
        'keterangan' => 'Pembayaran iuran',
        'jumlah' => 50000,
        'tanggal' => now()->toDateString(),
    ]);

    KasKeluar::create([
        'keterangan' => 'Perbaikan fasilitas',
        'jumlah' => 25000,
        'tanggal' => now()->toDateString(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertViewHas('rumahBelumBayarCount', 1);
    $response->assertViewHas('netBulanIni', 25000);
    $response->assertViewHas('totalRumahAktif', 1);
    $response->assertViewHas('rumahBelumBayarBulanIni', fn (array $items) => count($items) === 1);
    $response->assertViewHas('kasKeluarTerbesarBulanIni', fn (array $items) => $items[0]['jumlah'] === 25000);
    $response->assertViewHas('chartData', function (array $chartData) {
        return count($chartData['months']) === 12
            && $chartData['masukData'][11] === 50000
            && $chartData['keluarData'][11] === 25000;
    });
    $response->assertSee('Prioritas Hari Ini');
    $response->assertSee('Kas Keluar Terbesar Bulan Ini');

    $dailyResponse = $this->actingAs($admin)->get(route('admin.dashboard', ['chart' => 'daily']));

    $dailyResponse->assertOk();
    $dailyResponse->assertViewHas('chartData', function (array $chartData) {
        $todayIndex = now()->day - 1;

        return count($chartData['months']) === now()->daysInMonth
            && $chartData['masukData'][$todayIndex] === 50000
            && $chartData['keluarData'][$todayIndex] === 25000;
    });
});
