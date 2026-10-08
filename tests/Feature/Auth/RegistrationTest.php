<?php

use App\Models\Rt;
use App\Models\Rw;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen lists active rumah without exposing full address', function () {
    $rw = Rw::create(['name' => 'RW Register', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 02', 'is_active' => true]);

    Rumah::create([
        'kode_rumah' => 'REGISTER-001',
        'alamat' => 'Jl. Rahasia Register No. 88',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);

    Rumah::create([
        'kode_rumah' => 'REGISTER-NONAKTIF',
        'alamat' => 'Jl. Nonaktif Tidak Muncul',
        'rt_id' => $rt->id,
        'status' => 'nonaktif',
    ]);

    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('RT 02')
        ->assertSee('RT 02 - REGISTER-001')
        ->assertDontSee('Jl. Rahasia Register No. 88')
        ->assertDontSee('REGISTER-NONAKTIF')
        ->assertDontSee('Jl. Nonaktif Tidak Muncul');
});

test('new users can register and wait for rt verification', function () {
    $rw = Rw::create([
        'name' => 'RW 05',
        'is_active' => true,
    ]);

    $rt = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $rumah = Rumah::create([
        'kode_rumah' => 'REG-001',
        'alamat' => 'Jl. Registrasi No. 1',
        'rt' => '01',
        'rw' => '05',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '081234567890',
        'rumah_id' => $rumah->id,
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    $user = User::where('email', 'test@example.com')->firstOrFail();
    $warga = Warga::where('user_id', $user->id)->firstOrFail();

    expect($user->status_akun)->toBe('pending_verifikasi')
        ->and($user->rumah_id)->toBe($rumah->id)
        ->and($user->rt_id)->toBe($rt->id)
        ->and($warga->status_verifikasi)->toBe('pending')
        ->and($warga->kartu_keluarga_id)->toBeNull()
        ->and($warga->rumah_diajukan_id)->toBe($rumah->id);
});

test('registration rejects active rumah that is not linked to rt', function () {
    $rumah = Rumah::create([
        'kode_rumah' => 'REG-NO-RT',
        'alamat' => 'Jl. Belum Terhubung RT',
        'rt' => '01',
        'rw' => '05',
        'rt_id' => null,
        'status' => 'aktif',
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '081234567890',
        'rumah_id' => $rumah->id,
    ]);

    $response->assertSessionHasErrors([
        'rumah_id' => 'Rumah/domisili yang dipilih tidak tersedia atau belum terhubung ke RT.',
    ]);

    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
    $this->assertGuest();
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
