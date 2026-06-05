<?php

use App\Models\Role;
use App\Models\Rumah;
use App\Models\User;

function adminForRumahManagement(): User
{
    $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

function wargaRoleForRumahManagement(): Role
{
    return Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);
}

test('admin can update rumah status and penanggung jawab clearly', function () {
    $admin = adminForRumahManagement();
    $role = wargaRoleForRumahManagement();

    $rumah = Rumah::create([
        'kode_rumah' => 'R-01',
        'alamat' => 'Jl. Lama No. 1',
        'rt' => '001',
        'rw' => '002',
        'status' => 'aktif',
    ]);

    $oldHead = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $newHead = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_penanggung_jawab_rumah' => false,
    ]);

    $rumah->update(['penanggung_jawab_id' => $oldHead->id]);

    $response = $this->actingAs($admin)->patch(route('admin.rumah.update', $rumah), [
        'kode_rumah' => 'R-01',
        'alamat' => 'Jl. Melati No. 1',
        'rt' => '003',
        'rw' => '004',
        'status' => 'aktif',
        'penanggung_jawab_id' => $newHead->id,
    ]);

    $response->assertRedirect(route('admin.rumah.show', $rumah));

    $rumah->refresh();

    expect($rumah->alamat)->toBe('Jl. Melati No. 1');
    expect($rumah->rt)->toBe('003');
    expect($rumah->rw)->toBe('004');
    expect($rumah->penanggung_jawab_id)->toBe($newHead->id);
    expect($oldHead->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
    expect($newHead->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
});

test('admin can move warga to another active rumah and make them penanggung jawab', function () {
    $admin = adminForRumahManagement();
    $role = wargaRoleForRumahManagement();

    $origin = Rumah::create([
        'kode_rumah' => 'R-02',
        'alamat' => 'Jl. Asal No. 2',
        'status' => 'aktif',
    ]);

    $target = Rumah::create([
        'kode_rumah' => 'R-03',
        'alamat' => 'Jl. Tujuan No. 3',
        'status' => 'aktif',
    ]);

    $oldTargetHead = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $target->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $warga = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $origin->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $origin->update(['penanggung_jawab_id' => $warga->id]);
    $target->update(['penanggung_jawab_id' => $oldTargetHead->id]);

    $response = $this->actingAs($admin)->post(route('admin.rumah.warga.move', [$origin, $warga]), [
        'target_rumah_id' => $target->id,
        'make_penanggung_jawab' => true,
    ]);

    $response->assertRedirect(route('admin.rumah.show', $origin));

    expect($origin->fresh()->penanggung_jawab_id)->toBeNull();
    expect($origin->fresh()->status)->toBe('kosong');
    expect($target->fresh()->penanggung_jawab_id)->toBe($warga->id);
    expect($warga->fresh()->rumah_id)->toBe($target->id);
    expect($warga->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
    expect($oldTargetHead->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
});

test('admin cannot move warga to non active rumah', function () {
    $admin = adminForRumahManagement();
    $role = wargaRoleForRumahManagement();

    $origin = Rumah::create([
        'kode_rumah' => 'R-04',
        'status' => 'aktif',
    ]);

    $inactive = Rumah::create([
        'kode_rumah' => 'R-05',
        'status' => 'nonaktif',
    ]);

    $warga = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $origin->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.rumah.warga.move', [$origin, $warga]), [
        'target_rumah_id' => $inactive->id,
    ]);

    $response->assertSessionHasErrors('target_rumah_id');
    expect($warga->fresh()->rumah_id)->toBe($origin->id);
});

test('occupied rumah cannot be marked empty or inactive before residents are moved out', function () {
    $admin = adminForRumahManagement();
    $role = wargaRoleForRumahManagement();

    $rumah = Rumah::create([
        'kode_rumah' => 'R-06',
        'alamat' => 'Jl. Terisi No. 6',
        'status' => 'aktif',
    ]);

    User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.rumah.update', $rumah), [
        'kode_rumah' => 'R-06',
        'alamat' => 'Jl. Terisi No. 6',
        'status' => 'kosong',
    ]);

    $response->assertSessionHasErrors('status');
    expect($rumah->fresh()->status)->toBe('aktif');
});
