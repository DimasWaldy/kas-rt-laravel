<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->get('/forgot-password');

    $this->post('/forgot-password', [
        'email' => $user->email,
        '_token' => csrf_token(),
    ]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password link is rejected for unregistered email', function () {
    $this->get('/forgot-password');

    $response = $this->post('/forgot-password', [
        'email' => 'tidak-terdaftar@example.com',
        '_token' => csrf_token(),
    ]);

    $response->assertSessionHasErrors(['email' => 'Email ini tidak terdaftar di sistem kami.']);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->get('/forgot-password');

    $this->post('/forgot-password', [
        'email' => $user->email,
        '_token' => csrf_token(),
    ]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->get('/forgot-password');

    $this->post('/forgot-password', [
        'email' => $user->email,
        '_token' => csrf_token(),
    ]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->get('/reset-password/'.$notification->token);

        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
            '_token' => csrf_token(),
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
