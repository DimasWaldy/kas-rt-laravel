<?php

use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register and wait for rt verification', function () {
    $rumah = Rumah::create([
        'kode_rumah' => 'REG-001',
        'alamat' => 'Jl. Registrasi No. 1',
        'status' => 'aktif',
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '081234567890',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    $user = User::where('email', 'test@example.com')->firstOrFail();
    $warga = Warga::where('user_id', $user->id)->firstOrFail();

    expect($user->status_akun)->toBe('pending_verifikasi')
        ->and($warga->status_verifikasi)->toBe('pending')
        ->and($warga->kartu_keluarga_id)->toBeNull();
});

test('registration validates phone and name', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '0812abc567890123',
    ]);

    $response->assertSessionHasErrors(['phone', 'name']);
    $this->assertGuest();
});
