<?php

use App\Models\KartuKeluarga;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;

function roleDirektori(string $name): Role
{
    $role = Role::firstOrCreate(['name' => $name]);
    $permission = Permission::firstOrCreate(['name' => 'view-direktori-rw']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return $role;
}

function wargaDirektori(string $name, Rt $rt, string $email, string $phone, string $kodeRumah, string $nik, string $noKk): User
{
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rumah = Rumah::create([
        'kode_rumah' => $kodeRumah,
        'alamat' => "Alamat {$kodeRumah}",
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);
    $kk = KartuKeluarga::create([
        'no_kk' => $noKk,
        'rumah_id' => $rumah->id,
        'nama_kepala_keluarga' => $name,
    ]);
    $user = User::factory()->create([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'rt_id' => $rt->id,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'kartu_keluarga_id' => $kk->id,
        'nama_lengkap' => $name,
        'nik' => $nik,
        'status_dalam_kk' => 'kepala_keluarga',
    ]);

    return $user;
}

test('pengurus rt hanya melihat direktori rt sendiri', function () {
    $rw = Rw::create(['name' => 'RW Direktori']);
    $rtSatu = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);
    $rtDua = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 02']);

    wargaDirektori('Budi RT Satu', $rtSatu, 'budi.rt1@example.test', '081111111111', 'A-01', '3273000000000101', '3273000000000201');
    wargaDirektori('Cici RT Dua', $rtDua, 'cici.rt2@example.test', '082222222222', 'B-01', '3273000000000102', '3273000000000202');

    $ketuaRt = User::factory()->create([
        'role_id' => roleDirektori('ketua_rt')->id,
        'rt_id' => $rtSatu->id,
    ]);

    $this->actingAs($ketuaRt)
        ->get(route('direktori-rw.index'))
        ->assertOk()
        ->assertSee('RT 01')
        ->assertSee('Budi RT Satu')
        ->assertDontSee('RT 02')
        ->assertDontSee('Cici RT Dua');

    $this->actingAs($ketuaRt)
        ->get(route('direktori-rw.rt.show', $rtDua))
        ->assertNotFound();
});

test('pengurus rw melihat semua rt dalam rw aktif', function () {
    $rw = Rw::create(['name' => 'RW Aktif Direktori', 'is_active' => true]);
    $rtSatu = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);
    $rtDua = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 02']);

    wargaDirektori('Dede RT Satu', $rtSatu, 'dede.rt1@example.test', '083333333333', 'C-01', '3273000000000103', '3273000000000203');
    wargaDirektori('Euis RT Dua', $rtDua, 'euis.rt2@example.test', '084444444444', 'D-01', '3273000000000104', '3273000000000204');

    $ketuaRw = User::factory()->create([
        'role_id' => roleDirektori('ketua_rw')->id,
        'rt_id' => null,
    ]);

    $this->actingAs($ketuaRw)
        ->get(route('direktori-rw.index'))
        ->assertOk()
        ->assertSee('RT 01')
        ->assertSee('RT 02')
        ->assertSee('Dede RT Satu')
        ->assertSee('Euis RT Dua');
});

test('warga biasa tidak dapat mengakses direktori rw', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->get(route('direktori-rw.index'))
        ->assertForbidden();
});
