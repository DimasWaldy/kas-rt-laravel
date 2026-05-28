<?php

use App\Models\Role;
use App\Models\Rumah;
use App\Models\User;

test('resident can assign their rumah and become penanggung jawab from profile', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'name' => 'Warga Rumah Baru',
        'email' => 'warga-rumah@example.test',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Warga Rumah Baru',
        'email' => 'warga-rumah@example.test',
        'rumah_kode' => 'Z-01',
        'rumah_alamat' => 'Jl. Demo No. 1',
        'no_kk' => '3174000000000001',
        'phone' => '081234567890',
        'rt' => '001',
        'rw' => '002',
        'jumlah_anggota_keluarga' => 4,
        'is_kepala_keluarga' => '1',
        'is_penanggung_jawab_rumah' => '1',
    ]);

    $response->assertRedirect(route('profile.edit'));

    $rumah = Rumah::where('kode_rumah', 'Z-01')->first();

    expect($rumah)->not->toBeNull();
    expect($user->fresh()->rumah_id)->toBe($rumah->id);
    expect($user->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
    expect($rumah->fresh()->penanggung_jawab_id)->toBe($user->id);
});

test('resident cannot take over existing rumah penanggung jawab from profile', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'Z-02',
        'alamat' => 'Jl. Demo No. 2',
        'rt' => '001',
        'rw' => '002',
    ]);

    $existingPj = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $rumah->update(['penanggung_jawab_id' => $existingPj->id]);

    $challenger = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'name' => 'Calon PJ Baru',
        'email' => 'calon-pj@example.test',
        'is_penanggung_jawab_rumah' => false,
    ]);

    $response = $this->actingAs($challenger)->patch(route('profile.update'), [
        'name' => 'Calon PJ Baru',
        'email' => 'calon-pj@example.test',
        'rumah_id' => $rumah->id,
        'no_kk' => '3174000000000002',
        'phone' => '081234567891',
        'rt' => '001',
        'rw' => '002',
        'jumlah_anggota_keluarga' => 3,
        'is_kepala_keluarga' => '1',
        'is_penanggung_jawab_rumah' => '1',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error', 'Rumah ini sudah memiliki penanggung jawab iuran. Hubungi admin/RT untuk mengganti penanggung jawab.');

    expect($challenger->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
    expect($rumah->fresh()->penanggung_jawab_id)->toBe($existingPj->id);
});
