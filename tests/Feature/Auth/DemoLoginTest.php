<?php

use App\Models\User;

test('demo login is disabled when config app.demo_mode is false', function () {
    config(['app.demo_mode' => false]);

    $response = $this->post(route('demo-login'), [
        'account' => 'superadmin',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
});

test('demo login displays quick access buttons when enabled', function () {
    config(['app.demo_mode' => true]);

    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('Akses Cepat Demo')
        ->assertSee('Super Admin')
        ->assertSee('Admin RT')
        ->assertSee('Ketua RT')
        ->assertSee('Sekretaris RT')
        ->assertSee('Bendahara RT')
        ->assertSee('Warga');
});

test('user can login via demo login endpoint', function () {
    config(['app.demo_mode' => true]);

    $user = User::factory()->create([
        'email' => 'superadmin@smartrw.test',
        'status_akun' => 'aktif',
    ]);

    $response = $this->post(route('demo-login'), [
        'account' => 'superadmin',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('demo login fails gracefully if account not found', function () {
    config(['app.demo_mode' => true]);

    $response = $this->from('/login')->post(route('demo-login'), [
        'account' => 'superadmin',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
});
