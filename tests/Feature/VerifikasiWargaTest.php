<?php

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use App\Notifications\WargaTerverifikasi;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

function buatCalonWarga(
    array $userAttributes = [],
    array $wargaAttributes = []
): Warga {
    $role = Role::where('name', 'warga')->firstOrFail();

    $user = User::factory()->create(array_merge([
        'role_id' => $role->id,
        'status_akun' => 'pending_verifikasi',
        'phone' => '081234567890',
    ], $userAttributes));

    return Warga::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'status_verifikasi' => 'pending',
    ], $wargaAttributes));
}

beforeEach(function () {
    $this->seed([
        WilayahSeeder::class,
        RoleAndPermissionSeeder::class,
    ]);

    $this->rt = Rt::where('name', 'RT 01')->firstOrFail();

    $this->petugas = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->value('id'),
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);
});

test('warga pending tidak bisa login', function () {
    $warga = buatCalonWarga(['email' => 'pending@example.com']);

    $response = $this->post(route('login'), [
        'email' => 'pending@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('rt dapat memverifikasi warga dan mengaktifkan akun', function () {
    Notification::fake();

    $warga = buatCalonWarga();

    $response = $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'metode_verifikasi' => 'dokumen',
        ]);

    $response->assertRedirect(route('verifikasi-warga.index'));
    expect($warga->fresh()->status_verifikasi)->toBe('terverifikasi')
        ->and($warga->fresh()->metode_verifikasi)->toBe('dokumen')
        ->and($warga->user->fresh()->status_akun)->toBe('aktif')
        ->and($warga->fresh()->diverifikasi_oleh)->toBe($this->petugas->id);

    Notification::assertSentTo($warga->user, WargaTerverifikasi::class);
});

test('rt dapat menolak warga dengan alasan', function () {
    $warga = buatCalonWarga();

    $response = $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.tolak', $warga), [
            'catatan_verifikasi' => 'Alamat tidak dapat ditemukan oleh pengurus RT.',
        ]);

    $response->assertRedirect(route('verifikasi-warga.index'));
    expect($warga->fresh()->status_verifikasi)->toBe('ditolak')
        ->and($warga->fresh()->catatan_verifikasi)->toBe('Alamat tidak dapat ditemukan oleh pengurus RT.')
        ->and($warga->user->fresh()->status_akun)->toBe('ditolak')
        ->and($warga->fresh()->diverifikasi_oleh)->toBe($this->petugas->id);
});

test('setelah diverifikasi warga dapat login', function () {
    $warga = buatCalonWarga(['email' => 'warga@example.com']);

    $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'metode_verifikasi' => 'tatap_muka',
        ]);

    Auth::logout();

    $response = $this->post(route('login'), [
        'email' => 'warga@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($warga->user);
    $response->assertRedirect(route('dashboard'));
});

test('migration memindahkan user lama ke warga dan kartu keluarga tanpa kehilangan data', function () {
    Artisan::call('migrate:rollback', [
        '--step' => 3,
        '--force' => true,
    ]);

    $rumah = Rumah::create([
        'kode_rumah' => 'LEGACY-01',
        'alamat' => 'Jl. Data Lama No. 1',
        'rt_id' => $this->rt->id,
        'status' => 'aktif',
    ]);

    $legacyUserId = DB::table('users')->insertGetId([
        'name' => 'Warga Data Lama',
        'email' => 'legacy@example.test',
        'password' => bcrypt('password'),
        'role_id' => Role::where('name', 'warga')->value('id'),
        'rumah_id' => $rumah->id,
        'rt_id' => $this->rt->id,
        'no_kk' => '3273010101010099',
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
        'jumlah_anggota_keluarga' => 4,
        'phone' => '081299999999',
        'rt' => '01',
        'rw' => '05',
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ]);

    Artisan::call('migrate', ['--force' => true]);

    $kartuKeluarga = KartuKeluarga::where('no_kk', '3273010101010099')->firstOrFail();
    $warga = Warga::where('user_id', $legacyUserId)->firstOrFail();

    expect($kartuKeluarga->rumah_id)->toBe($rumah->id)
        ->and($kartuKeluarga->nama_kepala_keluarga)->toBe('Warga Data Lama')
        ->and($warga->kartu_keluarga_id)->toBe($kartuKeluarga->id)
        ->and($warga->nama_lengkap)->toBe('Warga Data Lama')
        ->and($warga->status_dalam_kk)->toBe('kepala_keluarga')
        ->and($warga->status_verifikasi)->toBe('terverifikasi')
        ->and(User::findOrFail($legacyUserId)->status_akun)->toBe('aktif');

    expect(Schema::hasColumn('users', 'no_kk'))->toBeFalse();
});
