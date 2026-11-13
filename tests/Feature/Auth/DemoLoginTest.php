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

test('demo login displays all 30 demo warga including friends in modal', function () {
    config(['app.demo_mode' => true]);

    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('Pilih Akun Demo Warga')
        ->assertSee('30 Akun')
        ->assertSee('Yazid')
        ->assertSee('Julius Wisnu Broto')
        ->assertSee('Shaifullah Shadik')
        ->assertSee('Fabian')
        ->assertSee('Halim Hafis')
        ->assertSee('Riki Ridwan')
        ->assertSee('Fadli')
        ->assertSee('Reival')
        ->assertSee('Juan')
        ->assertSee('Dimas')
        ->assertSee('Rafi')
        ->assertSee('Fendi')
        ->assertSee('Firman')
        ->assertSee('Toni')
        ->assertSee('Teman');
});

test('user can login as specific demo warga with email parameter', function () {
    config(['app.demo_mode' => true]);

    $user = User::factory()->create([
        'name' => 'Julius Wisnu Broto',
        'email' => 'julius@smartrw.test',
        'status_akun' => 'aktif',
    ]);

    $response = $this->post(route('demo-login'), [
        'account' => 'warga',
        'email' => 'julius@smartrw.test',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});
