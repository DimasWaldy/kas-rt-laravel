<?php

use App\Models\Rt;
use App\Models\Rw;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen no longer exposes houses or asks for domicile', function () {
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
        ->assertDontSee('RT 02 - REGISTER-001')
        ->assertDontSee('name="rumah_id"', false)
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
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    $user = User::where('email', 'test@example.com')->firstOrFail();
    $warga = Warga::where('user_id', $user->id)->firstOrFail();

    expect($user->status_akun)->toBe('pending_verifikasi')
        ->and($user->rumah_id)->toBeNull()
        ->and($user->rt_id)->toBeNull()
        ->and($user->onboarding_domisili)->toBeTrue()
        ->and($warga->status_verifikasi)->toBe('pending')
        ->and($warga->kartu_keluarga_id)->toBeNull()
        ->and($warga->rumah_diajukan_id)->toBeNull();
});

test('registration rejects domicile fields even if submitted manually', function () {
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
        'rumah_id' => 'Domisili diisi di dalam aplikasi setelah akun diverifikasi.',
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

test('registration tetap sukses dan melaporkan kegagalan listener setelah akun disimpan', function () {
    Exceptions::fake();
    $failure = new RuntimeException('Listener registrasi gagal');
    Event::listen(Registered::class, function () use ($failure) {
        throw $failure;
    });

    $rw = Rw::create(['name' => 'RW SMTP', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT SMTP', 'is_active' => true]);
    $rumah = Rumah::create(['kode_rumah' => 'SMTP-01', 'rt_id' => $rt->id, 'status' => 'aktif']);

    $this->post('/register', [
        'name' => 'Warga SMTP',
        'email' => 'smtp-failure@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '081234567890',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Pendaftaran berhasil! Masuk untuk memantau status verifikasi pengurus. Setelah disetujui, lanjutkan pengajuan domisili. Tidak perlu verifikasi email.');

    $user = User::where('email', 'smtp-failure@example.test')->sole();
    expect($user->status_akun)->toBe('pending_verifikasi')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->rt_id)->toBeNull();
    $this->assertDatabaseHas('wargas', [
        'user_id' => $user->id,
        'rumah_diajukan_id' => null,
        'status_verifikasi' => 'pending',
    ]);
    Exceptions::assertReported(fn (RuntimeException $e) => $e === $failure);
    $this->assertGuest();
});

test('registration does not send email verification or activate account automatically', function () {
    Notification::fake();
    $this->post('/register', [
        'name' => 'Tanpa Email Verifikasi', 'email' => 'no-verification@example.test',
        'password' => 'password', 'password_confirmation' => 'password', 'phone' => '081234567890',
    ])->assertRedirect(route('login'))->assertSessionHasNoErrors();
    $user = User::where('email', 'no-verification@example.test')->sole();
    expect($user->email_verified_at)->toBeNull()->and($user->status_akun)->toBe('pending_verifikasi');
    Notification::assertNothingSent();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
});
