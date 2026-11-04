<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('old email verification page redirects to dashboard without a verification prompt', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertRedirect(route('dashboard'));
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('active account can access dashboard and profile without verified email', function () {
    $user = User::factory()->unverified()->create([
        'status_akun' => 'aktif',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->get(route('profile.edit'))->assertOk()
        ->assertDontSee('Email Anda belum diverifikasi')
        ->assertDontSee('Kirim ulang email verifikasi')
        ->assertDontSee('send-verification');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('old signed verification link no longer marks the email as verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertRedirect(route('dashboard'));
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('old resend endpoint does not send email verification', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('dashboard'));
    Notification::assertNothingSent();
    expect($user->fresh()->email_verified_at)->toBeNull();
});
