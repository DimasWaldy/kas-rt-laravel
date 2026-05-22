<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
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
