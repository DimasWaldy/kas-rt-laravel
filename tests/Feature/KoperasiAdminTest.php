<?php

use App\Models\User;
use App\Models\Role;
use App\Models\KoperasiSimpanan;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiAngsuran;
use App\Models\Rt;
use App\Models\Rw;

use App\Models\Warga;

function buatWargaKoperasiAdmin(?Rt $rt = null) {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'rt_id' => $rt?->id,
        'status_akun' => 'aktif',
    ]);
    return Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'nik' => str_pad((string) $user->id, 16, '1', STR_PAD_LEFT),
        'no_kk' => '1234567890123456',
        'agama' => 'Islam',
        'jenis_kelamin' => 'Laki-laki',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'status_perkawinan' => 'Belum Kawin',
        'status_kependudukan' => 'Tetap',
        'kewarganegaraan' => 'WNI',
        'pekerjaan' => 'Swasta',
    ]);
}

use App\Models\Permission;

beforeEach(function () {
    $rw = Rw::create(['name' => 'RW Test Koperasi']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);

    $bendaharaRole = Role::firstOrCreate(['name' => 'bendahara']);

    $pManage = Permission::firstOrCreate(['name' => 'manage-koperasi']);
    $pApprove = Permission::firstOrCreate(['name' => 'approve-koperasi']);
    $bendaharaRole->permissions()->syncWithoutDetaching([$pManage->id, $pApprove->id]);

    $this->admin = User::factory()->create([
        'role_id' => $bendaharaRole->id,
        'rt_id' => $rt->id,
    ]);

    $this->wargaUser = buatWargaKoperasiAdmin($rt)->user;
});

test('admin dapat melihat dashboard koperasi', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.koperasi.index'))
        ->assertOk()
        ->assertSee('Kelola Koperasi');
});

test('bendahara dapat melihat pinjaman pending warga yang belum punya rt', function () {
    $wargaTanpaRt = buatWargaKoperasiAdmin()->user;

    $pinjaman = KoperasiPinjam::create([
        'user_id' => $wargaTanpaRt->id,
        'amount' => 750000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 90000,
        'remaining_amount' => 840000,
        'status' => 'menunggu_persetujuan',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.koperasi.index'))
        ->assertOk()
        ->assertSee($wargaTanpaRt->name)
        ->assertSee('Rp 750.000');

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjaman), [
            'status' => 'disetujui',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_pinjams', [
        'id' => $pinjaman->id,
        'status' => 'disetujui',
        'approved_by' => $this->admin->id,
    ]);
});

test('admin dapat memverifikasi simpanan', function () {
    $simpanan = KoperasiSimpanan::create([
        'user_id' => $this->wargaUser->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-simpanan', $simpanan->id), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_simpanans', [
        'id' => $simpanan->id,
        'status' => 'terverifikasi',
        'verified_by' => $this->admin->id,
    ]);
});

test('admin dapat menyetujui pinjaman', function () {
    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'menunggu_persetujuan',
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->image('bukti_transfer.jpg');

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjaman->id), [
            'status' => 'disetujui',
            'proof_file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_pinjams', [
        'id' => $pinjaman->id,
        'status' => 'disetujui',
        'approved_by' => $this->admin->id,
    ]);
});

test('admin dapat memverifikasi angsuran dan sisa pinjaman berkurang', function () {
    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'disetujui',
    ]);

    $angsuran = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjaman->id,
        'amount' => 120000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-angsuran', $angsuran->id), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_angsurans', [
        'id' => $angsuran->id,
        'status' => 'terverifikasi',
    ]);

    $this->assertDatabaseHas('koperasi_pinjams', [
        'id' => $pinjaman->id,
        'remaining_amount' => 1200000 - 120000,
    ]);
});
