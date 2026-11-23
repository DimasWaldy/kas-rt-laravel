<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'phone' => '081234567890',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'konfirmasi',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
            'confirmation' => 'konfirmasi',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('exact confirmation text must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile');

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'salah',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'confirmation')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('user can upload profile photo and view it', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 300, 300)->size(500);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Dimas Ganteng',
            'email' => $user->email,
            'phone' => '081234567890',
            'foto_profil' => $file,
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertNotNull($user->foto_profil);
    Storage::disk('local')->assertExists($user->foto_profil);

    $fotoResponse = $this->actingAs($user)->get(route('users.foto', $user));
    $fotoResponse->assertOk();
});

test('profile photo rejects non-image or files exceeding 2MB', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $badFile = UploadedFile::fake()->create('document.pdf', 3000, 'application/pdf');

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Dimas',
            'email' => $user->email,
            'phone' => '081234567890',
            'foto_profil' => $badFile,
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasErrors('foto_profil')
        ->assertRedirect('/profile');
});

test('user can remove profile photo', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.png');
    $path = $file->store('foto_profil', 'local');
    $user->update(['foto_profil' => $path]);

    Storage::disk('local')->assertExists($path);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '081234567890',
            'hapus_foto' => '1',
            '_token' => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    $this->assertNull($user->foto_profil);
    Storage::disk('local')->assertMissing($path);
});
