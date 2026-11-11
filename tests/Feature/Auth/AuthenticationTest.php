<?php

use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('Kembali ke Beranda')
        ->assertSee('href="'.url('/').'"', false);
});

test('login screen registration panel does not expose houses or addresses', function () {
    $rw = Rw::create(['name' => 'RW Login', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01', 'is_active' => true]);

    Rumah::create([
        'kode_rumah' => 'LOGIN-001',
        'alamat' => 'Jl. Rahasia Login No. 99',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);

    $response = $this->get('/login');

    $response->assertOk()
        ->assertDontSee('RT 01 - LOGIN-001')
        ->assertDontSee('name="rumah_id"', false)
        ->assertDontSee('Jl. Rahasia Login No. 99');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $this->get('/login');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        '_token' => csrf_token(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->get('/login');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        '_token' => csrf_token(),
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->get('/dashboard');

    $response = $this->post('/logout', [
        '_token' => csrf_token(),
    ]);

    $this->assertGuest();
    $response->assertRedirect('/');
});
