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
use Illuminate\Support\Facades\Storage;

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

test('warga pending login hanya menuju dashboard terkunci', function () {
    $warga = buatCalonWarga(['email' => 'pending@example.com']);

    $response = $this->post(route('login'), [
        'email' => 'pending@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($warga->user);
    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Pendaftaran Anda sedang diperiksa');
});

test('rt dapat memverifikasi warga dan mengaktifkan akun', function () {
    Notification::fake();
    Storage::fake('local');

    Storage::disk('local')->put('verifikasi/ktp.pdf', 'dokumen identitas sintetis');
    $warga = buatCalonWarga(['rt_id' => $this->rt->id], ['dokumen_ktp' => 'verifikasi/ktp.pdf']);

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
    $warga = buatCalonWarga(['rt_id' => $this->rt->id]);

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
    $warga = buatCalonWarga([
        'email' => 'warga@example.com',
        'rt_id' => $this->rt->id,
    ]);

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

test('rt hanya melihat calon warga sesuai wilayahnya', function () {
    $rtLain = Rt::where('name', 'RT 02')->firstOrFail();

    $rumahRtIni = Rumah::create([
        'kode_rumah' => 'VRF-RT-01',
        'alamat' => 'Jl. Verifikasi RT 01',
        'rt_id' => $this->rt->id,
        'status' => 'aktif',
    ]);

    buatCalonWarga(
        ['name' => 'Calon RT Sendiri', 'rt_id' => $this->rt->id],
        ['nama_lengkap' => 'Calon RT Sendiri']
    );

    buatCalonWarga(
        ['name' => 'Calon Legacy Rumah', 'rt_id' => null],
        ['nama_lengkap' => 'Calon Legacy Rumah', 'rumah_diajukan_id' => $rumahRtIni->id]
    );

    buatCalonWarga(
        ['name' => 'Calon RT Lain', 'rt_id' => $rtLain->id],
        ['nama_lengkap' => 'Calon RT Lain']
    );

    buatCalonWarga(
        ['name' => 'Calon Tanpa Domisili', 'rt_id' => null],
        ['nama_lengkap' => 'Calon Tanpa Domisili']
    );

    $response = $this->actingAs($this->petugas)
        ->get(route('verifikasi-warga.index'));

    $response->assertOk()
        ->assertSee('Calon RT Sendiri')
        ->assertSee('Calon Legacy Rumah')
        ->assertDontSee('Calon RT Lain')
        ->assertDontSee('Calon Tanpa Domisili');
});

test('rt tidak dapat memproses calon warga rt lain', function () {
    $rtLain = Rt::where('name', 'RT 02')->firstOrFail();
    $warga = buatCalonWarga(['rt_id' => $rtLain->id]);

    $this->actingAs($this->petugas)
        ->get(route('verifikasi-warga.show', $warga))
        ->assertNotFound();

    $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'metode_verifikasi' => 'dokumen',
        ])
        ->assertNotFound();

    expect($warga->fresh()->status_verifikasi)->toBe('pending')
        ->and($warga->user->fresh()->status_akun)->toBe('pending_verifikasi');
});

test('api kk rumah hanya mengembalikan kk rumah yang boleh diakses petugas', function () {
    $rtLain = Rt::where('name', 'RT 02')->firstOrFail();

    $rumahRtIni = Rumah::create([
        'kode_rumah' => 'KK-RT-01',
        'alamat' => 'Jl. KK RT 01',
        'rt_id' => $this->rt->id,
        'status' => 'aktif',
    ]);

    $rumahRtLain = Rumah::create([
        'kode_rumah' => 'KK-RT-02',
        'alamat' => 'Jl. KK RT 02',
        'rt_id' => $rtLain->id,
        'status' => 'aktif',
    ]);

    $kk = KartuKeluarga::create([
        'rumah_id' => $rumahRtIni->id,
        'no_kk' => '3273010101010101',
        'nama_kepala_keluarga' => 'Budi Santoso',
    ]);

    KartuKeluarga::create([
        'rumah_id' => $rumahRtLain->id,
        'no_kk' => '3273010101010102',
        'nama_kepala_keluarga' => 'Warga RT Lain',
    ]);

    $this->actingAs($this->petugas)
        ->getJson(route('api.rumah.kk', $rumahRtIni))
        ->assertOk()
        ->assertJson([
            [
                'id' => $kk->id,
                'no_kk' => '3273010101010101',
                'nama_kepala' => 'Budi Santoso',
            ],
        ])
        ->assertJsonMissing([
            'no_kk' => '3273010101010102',
        ]);

    $this->actingAs($this->petugas)
        ->getJson(route('api.rumah.kk', $rumahRtLain))
        ->assertNotFound();
});

test('migration memindahkan user lama ke warga dan kartu keluarga tanpa kehilangan data', function () {
    $legacyRefactorMigration = '2026_06_29_000003_refactor_users_remove_kependudukan_fields';
    $rollbackSteps = DB::table('migrations')
        ->where('migration', '>=', $legacyRefactorMigration)
        ->count();

    expect($rollbackSteps)->toBeGreaterThan(0);

    Artisan::call('migrate:rollback', [
        '--step' => $rollbackSteps,
        '--force' => true,
    ]);

    expect(Schema::hasColumn('users', 'no_kk'))->toBeTrue();

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
