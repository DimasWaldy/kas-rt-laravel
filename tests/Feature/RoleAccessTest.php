<?php

use App\Models\Role;
use App\Models\User;

function userWithRole(string $role): User
{
    $roleModel = Role::firstOrCreate(
        ['name' => $role],
        ['description' => ucfirst($role)]
    );

    return User::factory()->create([
        'role_id' => $roleModel->id,
    ]);
}

test('warga cannot access finance input pages directly', function () {
    $warga = userWithRole('warga');

    $response = $this->actingAs($warga)->get(route('kas-masuk.create'));

    $response->assertForbidden();
    $response->assertSee('Halaman ini hanya dapat diakses oleh admin atau bendahara.');
});

test('bendahara cannot access warga management pages directly', function () {
    $bendahara = userWithRole('bendahara');

    $response = $this->actingAs($bendahara)->get(route('admin.warga.index'));

    $response->assertForbidden();
    $response->assertSee('Halaman ini hanya dapat diakses oleh admin atau sekretaris.');
});

test('sekretaris cannot access finance verification pages directly', function () {
    $sekretaris = userWithRole('sekretaris');

    $response = $this->actingAs($sekretaris)->get(route('tagihan.admin'));

    $response->assertForbidden();
    $response->assertSee('Halaman ini hanya dapat diakses oleh admin atau bendahara.');
});

test('sekretaris cannot submit finance verification directly', function () {
    $sekretaris = userWithRole('sekretaris');

    $response = $this->actingAs($sekretaris)->post(route('tagihan.confirm'), [
        'tagihan_id' => 1,
        'status' => 'lunas',
    ]);

    $response->assertForbidden();
    $response->assertSee('Halaman ini hanya dapat diakses oleh admin atau bendahara.');
});

test('warga cannot access admin dashboard or warga management routes', function () {
    $warga = userWithRole('warga');

    $this->actingAs($warga)
        ->get(route('admin.dashboard'))
        ->assertForbidden()
        ->assertSee('Halaman ini hanya dapat diakses oleh admin.');

    $this->actingAs($warga)
        ->get(route('admin.warga.index'))
        ->assertForbidden()
        ->assertSee('Halaman ini hanya dapat diakses oleh admin atau sekretaris.');
});

test('bendahara cannot create or update warga data directly', function () {
    $bendahara = userWithRole('bendahara');
    $warga = userWithRole('warga');

    $this->actingAs($bendahara)
        ->post(route('admin.warga.store'), [
            'name' => 'Warga Ditolak',
            'email' => 'ditolak@example.com',
            'password' => 'password',
        ])
        ->assertForbidden()
        ->assertSee('Halaman ini hanya dapat diakses oleh admin atau sekretaris.');

    $this->actingAs($bendahara)
        ->patch(route('admin.warga.update', $warga), [
            'name' => 'Update Ditolak',
            'email' => $warga->email,
        ])
        ->assertForbidden()
        ->assertSee('Halaman ini hanya dapat diakses oleh admin atau sekretaris.');
});

test('bendahara cannot access admin dashboard directly', function () {
    $bendahara = userWithRole('bendahara');

    $response = $this->actingAs($bendahara)->get(route('admin.dashboard'));

    $response->assertForbidden();
    $response->assertSee('Halaman ini hanya dapat diakses oleh admin.');
});
