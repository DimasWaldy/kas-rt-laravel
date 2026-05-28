<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Tagihan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Gunakan firstOrCreate agar tidak duplikat antar test case
    $this->wargaRole = Role::firstOrCreate(
        ['name' => 'warga'],
        ['description' => 'Resident Role']
    );

    $this->adminRole = Role::firstOrCreate(
        ['name' => 'admin'],
        ['description' => 'Admin Role']
    );
});

test('resident can pay bill using bank transfer with valid file', function () {
    Storage::fake('public');

    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    $bukti = UploadedFile::fake()->image('payment_proof.jpg');

    $this->actingAs($resident)->get(route('tagihan.index'));

    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $bukti,
        'note'           => 'Paid via Mobile Banking',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();
    expect($tagihan->status)->toBe('pending_transfer');
    expect($tagihan->payment_method)->toBe('transfer');
    expect($tagihan->note)->toBe('Paid via Mobile Banking');
    expect($tagihan->bukti)->not->toBeNull();

    Storage::disk('public')->assertExists($tagihan->bukti);
});

test('transfer payment fails validation when file is missing, invalid type, or too large', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    // Case 1: Missing file
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Bukti pembayaran wajib diunggah untuk metode transfer.']);

    // Case 2: Invalid extension (e.g. txt)
    $invalidFile = UploadedFile::fake()->create('proof.txt', 100);
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $invalidFile,
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Format bukti pembayaran harus berupa gambar (jpeg, png, jpg) atau PDF.']);

    // Case 3: Too large (4MB > 3MB limit)
    $largeFile = UploadedFile::fake()->create('proof.jpg', 4000);
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'transfer',
        'bukti'          => $largeFile,
    ]);

    $response->assertSessionHasErrors(['bukti' => 'Ukuran berkas bukti pembayaran maksimal adalah 3 MB (3072 KB).']);
});

test('resident can pay offline with a valid note', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    $this->actingAs($resident)->get(route('tagihan.index'));

    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
        'note'           => 'Diserahkan langsung ke Pak RT',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tagihan.index'));

    $tagihan->refresh();
    expect($tagihan->status)->toBe('pending_offline');
    expect($tagihan->payment_method)->toBe('offline');
    expect($tagihan->note)->toBe('Diserahkan langsung ke Pak RT');
    expect($tagihan->bukti)->toBeNull();
});

test('offline payment fails validation when note is missing or too short', function () {
    $resident = User::factory()->create([
        'role_id'           => $this->wargaRole->id,
        'is_kepala_keluarga' => true,
        'no_kk'             => '1234567890123456',
    ]);

    $tagihan = Tagihan::create([
        'user_id' => $resident->id,
        'bulan'   => 5,
        'tahun'   => 2026,
        'total'   => 50000,
        'status'  => 'belum_bayar',
    ]);

    // Case 1: Missing note
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
    ]);

    $response->assertSessionHasErrors(['note' => 'Catatan wajib diisi untuk pembayaran offline (misal: diserahkan ke siapa & tanggal).']);

    // Case 2: Note is too short (under 5 chars)
    $this->actingAs($resident)->get(route('tagihan.index'));
    $response = $this->post(route('tagihan.pay'), [
        '_token'         => csrf_token(),
        'tagihan_id'     => $tagihan->id,
        'payment_method' => 'offline',
        'note'           => 'RT',
    ]);

    $response->assertSessionHasErrors(['note' => 'Catatan pembayaran offline minimal harus terdiri dari 5 karakter.']);
});
