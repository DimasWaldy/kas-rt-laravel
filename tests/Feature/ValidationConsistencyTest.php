<?php

use App\Models\Role;
use App\Models\User;

function validationUserWithRole(string $role): User
{
    $roleModel = Role::firstOrCreate(
        ['name' => $role],
        ['description' => ucfirst($role)]
    );

    return User::factory()->create([
        'role_id' => $roleModel->id,
    ]);
}

test('profile rejects invalid phone number', function () {
    $warga = validationUserWithRole('warga');

    $response = $this->actingAs($warga)->patch(route('profile.update'), [
        'name' => $warga->name,
        'email' => $warga->email,
        'phone' => '0812abc',
    ]);

    $response->assertSessionHasErrors([
        'phone',
    ]);
});

test('admin warga form uses the same numeric validation rules', function () {
    $admin = validationUserWithRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.warga.store'), [
        'name' => 'Warga Baru',
        'email' => 'warga-baru@example.com',
        'password' => 'password',
        'no_kk' => '123456789012345',
        'nik' => '123456789012345',
        'phone' => '08123nomor',
    ]);

    $response->assertSessionHasErrors([
        'no_kk',
        'nik',
        'phone',
    ]);
});

test('iuran bulanan cannot be saved with zero amount', function () {
    $bendahara = validationUserWithRole('bendahara');

    $response = $this->actingAs($bendahara)->post(route('iuran-bulanan.store'), [
        'nama' => 'Iuran Nol',
        'keterangan' => 'Tidak valid',
        'jumlah' => 0,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'is_wajib' => true,
    ]);

    $response->assertSessionHasErrors('jumlah');
});
