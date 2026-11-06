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

test('pengurus rw melihat semua rt dalam rw yang ditugaskan', function () {
    $rw = Rw::create(['name' => 'RW Aktif Direktori', 'is_active' => true]);
    $rtSatu = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);
    $rtDua = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 02']);

    wargaDirektori('Dede RT Satu', $rtSatu, 'dede.rt1@example.test', '083333333333', 'C-01', '3273000000000103', '3273000000000203');
    wargaDirektori('Euis RT Dua', $rtDua, 'euis.rt2@example.test', '084444444444', 'D-01', '3273000000000104', '3273000000000204');

    $ketuaRw = User::factory()->create([
        'role_id' => roleDirektori('ketua_rw')->id,
        'rt_id' => $rtSatu->id,
    ]);

    $this->actingAs($ketuaRw)
        ->get(route('direktori-rw.index'))
        ->assertOk()
        ->assertSee('RT 01')
        ->assertSee('RT 02')
        ->assertSee('Dede RT Satu')
        ->assertSee('Euis RT Dua');
});

test('pengurus rw tanpa penugasan tidak dapat membuka direktori wilayah', function () {
    $rw = Rw::create(['name' => 'RW Privat', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Privat']);
    wargaDirektori('Warga Privat', $rt, 'privat@example.test', '085555555555', 'P-01', '3273000000000199', '3273000000000299');

    $ketuaRw = User::factory()->create([
        'role_id' => roleDirektori('ketua_rw')->id,
        'rt_id' => null,
    ]);

    $this->actingAs($ketuaRw)->get(route('direktori-rw.index'))->assertForbidden();
    $this->actingAs($ketuaRw)->get(route('direktori-rw.rt.show', $rt))->assertForbidden();
});

test('global operator without rt sees directory across active rw', function () {
    $rwFirst = Rw::create(['name' => 'RW Pertama', 'is_active' => true]);
    $rwSecond = Rw::create(['name' => 'RW Kedua', 'is_active' => true]);
    $rtFirst = Rt::create(['rw_id' => $rwFirst->id, 'name' => 'RT Pertama']);
    $rtSecond = Rt::create(['rw_id' => $rwSecond->id, 'name' => 'RT Kedua']);
    wargaDirektori('Warga RW Pertama', $rtFirst, 'pertama@example.test', '081111111111', 'R1-01', '3273000000000101', '3273000000000201');
    wargaDirektori('Warga RW Kedua', $rtSecond, 'kedua@example.test', '082222222222', 'R2-01', '3273000000000102', '3273000000000202');
    $operator = User::factory()->create(['role_id' => roleDirektori('super_admin')->id, 'rt_id' => null]);

    $this->actingAs($operator)->get(route('direktori-rw.index'))
        ->assertOk()->assertSee('Warga RW Pertama')->assertSee('Warga RW Kedua');
    $this->get(route('direktori-rw.rt.show', $rtSecond))->assertOk()->assertSee('Warga RW Kedua');
});

test('warga biasa tidak dapat mengakses direktori rw', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->get(route('direktori-rw.index'))
        ->assertForbidden();
});

test('direktori menyamarkan nomor hp dan kk warga di daftar dan detail rt', function (string $routeName) {
    $rw = Rw::create(['name' => 'RW Data Privat', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Data Privat', 'is_active' => true]);
    $resident = wargaDirektori('Warga Privat', $rt, 'private@example.test', '081234567890', 'PRIVATE-01', '3273010101900001', '3273000000000201');
    $actor = User::factory()->create(['role_id' => roleDirektori('ketua_rw')->id, 'rt_id' => $rt->id]);

    $this->actingAs($actor)
        ->get(route($routeName, $routeName === 'direktori-rw.rt.show' ? [$rt] : []))
        ->assertOk()
        ->assertSee('********7890')
        ->assertSee('3273********0201')
        ->assertDontSee('081234567890')
        ->assertDontSee('3273000000000201')
        ->assertDontSee('wa.me/081234567890');

    expect($resident->fresh()->phone)->toBe('081234567890')
        ->and($resident->fresh()->warga->kartuKeluarga->no_kk)->toBe('3273000000000201');
})->with(['direktori-rw.index', 'direktori-rw.rt.show']);

test('daftar warga direktori menyamarkan nik tanpa mengubah data', function (string $routeName) {
    $rw = Rw::create(['name' => 'RW Mask', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Mask', 'is_active' => true]);
    $resident = wargaDirektori('Warga Mask', $rt, 'mask@example.test', '081234567890', 'MASK-01', '3273010101900001', '3273000000000201');
    $actor = User::factory()->create(['role_id' => roleDirektori('ketua_rw')->id, 'rt_id' => $rt->id]);

    $this->actingAs($actor)
        ->get(route($routeName, $routeName === 'direktori-rw.rt.show' ? [$rt] : []))
        ->assertOk()
        ->assertSee('3273********0001')
        ->assertDontSee('3273010101900001');

    expect($resident->fresh()->warga->nik)->toBe('3273010101900001');
})->with(['direktori-rw.index', 'direktori-rw.rt.show']);
