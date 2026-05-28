<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->get('/register');

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'no_kk' => '1234567890123456',
        'is_kepala_keluarga' => true,
        'jumlah_anggota_keluarga' => 4,
        'phone' => '081234567890',
        'rt' => '001',
        'rw' => '002',
        '_token' => csrf_token(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration requires numeric kk and phone with valid lengths', function () {
    $this->get('/register');

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'no_kk' => '1234abcd56789012',
        'is_kepala_keluarga' => true,
        'jumlah_anggota_keluarga' => 4,
        'phone' => '0812abc567890123',
        'rt' => '001',
        'rw' => '002',
        '_token' => csrf_token(),
    ]);

    $response->assertSessionHasErrors(['no_kk', 'phone']);
    $this->assertGuest();
});
