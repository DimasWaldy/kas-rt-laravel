<?php

use App\Models\IuranBulanan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $rw = Rw::create([
        'name' => 'RW Finalisasi',
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
});

test('smart rw roles receive the expected operational access', function () {
    $superAdmin = User::factory()->create([
        'role_id' => Role::where('name', 'super_admin')->value('id'),
    ]);

    $bendaharaRw = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rw')->value('id'),
    ]);

    $sekretarisRt = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $bendaharaRt = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    $this->actingAs($bendaharaRw)
        ->get(route('laporan-kas.index'))
        ->assertOk();

    $this->actingAs($bendaharaRw)
        ->get(route('kas-keluar.create'))
        ->assertForbidden();

    $this->actingAs($sekretarisRt)
        ->get(route('admin.warga.index'))
        ->assertOk();

    $this->actingAs($sekretarisRt)
        ->get(route('tagihan.admin'))
        ->assertForbidden();

    $this->actingAs($bendaharaRt)
        ->get(route('tagihan.admin'))
        ->assertOk();
});

test('rt finance officer generates bills only for their rt while global operator covers all rts', function () {
    $wargaRoleId = Role::where('name', 'warga')->value('id');

    $wargaRtSatu = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtSatu->id,
    ]);

    $wargaRtDua = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtDua->id,
    ]);

    $rumahRtSatu = Rumah::create([
        'kode_rumah' => 'FINAL-RT1',
        'rt_id' => $this->rtSatu->id,
        'penanggung_jawab_id' => $wargaRtSatu->id,
        'status' => 'aktif',
    ]);

    $rumahRtDua = Rumah::create([
        'kode_rumah' => 'FINAL-RT2',
        'rt_id' => $this->rtDua->id,
        'penanggung_jawab_id' => $wargaRtDua->id,
        'status' => 'aktif',
    ]);

    $wargaRtSatu->update([
        'rumah_id' => $rumahRtSatu->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $wargaRtDua->update([
        'rumah_id' => $rumahRtDua->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 25000,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'is_wajib' => true,
    ]);

    $bendaharaRt = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $superAdmin = User::factory()->create([
        'role_id' => Role::where('name', 'super_admin')->value('id'),
    ]);

    Tagihan::generate(now()->month, now()->year, $bendaharaRt);

    expect(Tagihan::where('rt_id', $this->rtSatu->id)->count())->toBe(1);
    expect(Tagihan::where('rt_id', $this->rtDua->id)->count())->toBe(0);

    Tagihan::generate(now()->month, now()->year, $superAdmin);

    expect(Tagihan::where('rt_id', $this->rtSatu->id)->count())->toBe(1);
    expect(Tagihan::where('rt_id', $this->rtDua->id)->count())->toBe(1);
});

test('navigation labels and actions match rw and rt responsibilities', function () {
    $bendaharaRw = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rw')->value('id'),
    ]);

    $bendaharaRt = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->actingAs($bendaharaRw)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Bendahara RW')
        ->assertSee('Laporan Kas')
        ->assertSee('Rekap Keuangan RW')
        ->assertDontSee('Status Iuran Anda')
        ->assertDontSee('href="' . route('kas-keluar.index') . '"', false)
        ->assertDontSee('href="' . route('tagihan.admin') . '"', false);

    $this->actingAs($bendaharaRt)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Bendahara RT')
        ->assertSee('href="' . route('kas-keluar.index') . '"', false)
        ->assertSee('href="' . route('tagihan.admin') . '"', false);
});
