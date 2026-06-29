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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

function buatCalonWarga(
    Rt $rt,
    Rumah $rumah,
    array $userAttributes = [],
    array $wargaAttributes = []
): Warga {
    $role = Role::where('name', 'warga')->firstOrFail();

    $user = User::factory()->create(array_merge([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'rt_id' => $rt->id,
        'status_akun' => 'pending_verifikasi',
        'phone' => '081234567890',
    ], $userAttributes));

    return Warga::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'status_dalam_kk' => 'anggota',
        'status_verifikasi' => 'pending',
        'rumah_diajukan_id' => $rumah->id,
    ], $wargaAttributes));
}

beforeEach(function () {
    $this->seed([
        WilayahSeeder::class,
        RoleAndPermissionSeeder::class,
    ]);

    $this->rt = Rt::where('name', 'RT 01')->firstOrFail();
    $this->rumah = Rumah::create([
        'kode_rumah' => 'TEST-VER-01',
        'alamat' => 'Jl. Verifikasi No. 1',
        'rt' => '01',
        'rw' => '05',
        'rt_id' => $this->rt->id,
        'status' => 'aktif',
    ]);

    $this->petugas = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->value('id'),
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);
});

test('warga baru mendaftar tanpa nomor kk dan menjadi pending verifikasi', function () {
    $screen = $this->get(route('register'));

    $screen->assertOk()
        ->assertDontSee('name="no_kk"', false)
        ->assertDontSee('name="jumlah_anggota_keluarga"', false);

    $response = $this->post(route('register'), [
        'name' => 'Akun Warga Baru',
        'email' => 'warga.baru@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '081234567899',
        'rumah_id' => $this->rumah->id,
        'nama_lengkap' => 'Warga Baru Lengkap',
        'status_dalam_kk' => 'anggota',
    ]);

    $user = User::where('email', 'warga.baru@example.test')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('verifikasi.menunggu'));
    expect($user->status_akun)->toBe('pending_verifikasi')
        ->and($user->warga->status_verifikasi)->toBe('pending')
        ->and($user->warga->kartu_keluarga_id)->toBeNull()
        ->and($user->warga->nik)->toBeNull();

    expect(Schema::hasColumn('users', 'no_kk'))->toBeFalse();
});

test('warga pending dialihkan dari seluruh fitur warga ke halaman menunggu', function () {
    $warga = buatCalonWarga($this->rt, $this->rumah);

    $this->actingAs($warga->user)
        ->get(route('tagihan.index'))
        ->assertRedirect(route('verifikasi.menunggu'));

    $this->actingAs($warga->user)
        ->get(route('surat.index'))
        ->assertRedirect(route('verifikasi.menunggu'));

    $this->actingAs($warga->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verifikasi.menunggu'));
});

test('rt dapat memverifikasi warga melalui dokumen dan mengaktifkan akun', function () {
    Notification::fake();

    $warga = buatCalonWarga(
        $this->rt,
        $this->rumah,
        [],
        [
            'status_dalam_kk' => 'kepala_keluarga',
            'metode_verifikasi' => 'dokumen',
            'dokumen_kk' => 'verifikasi-warga/kk-demo.pdf',
            'dokumen_ktp' => 'verifikasi-warga/ktp-demo.pdf',
        ]
    );

    $response = $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'rumah_id' => $this->rumah->id,
            'status_dalam_kk' => 'kepala_keluarga',
            'no_kk' => '3273010101010001',
            'nik' => '3273010202020001',
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
    $warga = buatCalonWarga($this->rt, $this->rumah);

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

test('verifikasi kepala keluarga otomatis membuat kartu keluarga baru', function () {
    $warga = buatCalonWarga(
        $this->rt,
        $this->rumah,
        [],
        ['status_dalam_kk' => 'kepala_keluarga']
    );

    $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'rumah_id' => $this->rumah->id,
            'status_dalam_kk' => 'kepala_keluarga',
            'no_kk' => '3273010101010002',
            'nik' => '3273010202020002',
            'metode_verifikasi' => 'tatap_muka',
        ])
        ->assertRedirect(route('verifikasi-warga.index'));

    $kartuKeluarga = KartuKeluarga::where('no_kk', '3273010101010002')->firstOrFail();

    expect($kartuKeluarga->rumah_id)->toBe($this->rumah->id)
        ->and($kartuKeluarga->nama_kepala_keluarga)->toBe($warga->nama_lengkap)
        ->and($warga->fresh()->kartu_keluarga_id)->toBe($kartuKeluarga->id);
});

test('verifikasi anggota menghubungkan warga ke kk yang ada di rumah yang sama', function () {
    $kartuKeluarga = KartuKeluarga::create([
        'no_kk' => '3273010101010003',
        'rumah_id' => $this->rumah->id,
        'nama_kepala_keluarga' => 'Kepala Keluarga Lama',
    ]);
    $warga = buatCalonWarga($this->rt, $this->rumah);

    $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'rumah_id' => $this->rumah->id,
            'status_dalam_kk' => 'anggota',
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'nik' => '3273010202020003',
            'metode_verifikasi' => 'tatap_muka',
        ])
        ->assertRedirect(route('verifikasi-warga.index'));

    expect($warga->fresh()->kartu_keluarga_id)->toBe($kartuKeluarga->id)
        ->and($warga->fresh()->rumah_aktual->id)->toBe($this->rumah->id);
});

test('satu rumah dapat memiliki lebih dari satu kartu keluarga', function () {
    $wargaPertama = buatCalonWarga(
        $this->rt,
        $this->rumah,
        ['email' => 'kepala.satu@example.test'],
        ['status_dalam_kk' => 'kepala_keluarga']
    );
    $wargaKedua = buatCalonWarga(
        $this->rt,
        $this->rumah,
        ['email' => 'kepala.dua@example.test'],
        ['status_dalam_kk' => 'kepala_keluarga']
    );

    foreach ([
        [$wargaPertama, '3273010101010004', '3273010202020004'],
        [$wargaKedua, '3273010101010005', '3273010202020005'],
    ] as [$warga, $noKk, $nik]) {
        $this->actingAs($this->petugas)
            ->patch(route('verifikasi-warga.verifikasi', $warga), [
                'rumah_id' => $this->rumah->id,
                'status_dalam_kk' => 'kepala_keluarga',
                'no_kk' => $noKk,
                'nik' => $nik,
                'metode_verifikasi' => 'tatap_muka',
            ])
            ->assertRedirect(route('verifikasi-warga.index'));
    }

    expect(KartuKeluarga::where('rumah_id', $this->rumah->id)->count())->toBe(2);
});

test('setelah diverifikasi warga dapat mengakses fitur normal', function () {
    $warga = buatCalonWarga(
        $this->rt,
        $this->rumah,
        [],
        ['status_dalam_kk' => 'kepala_keluarga']
    );

    $this->actingAs($warga->user)
        ->get(route('tagihan.index'))
        ->assertRedirect(route('verifikasi.menunggu'));

    $this->actingAs($this->petugas)
        ->patch(route('verifikasi-warga.verifikasi', $warga), [
            'rumah_id' => $this->rumah->id,
            'status_dalam_kk' => 'kepala_keluarga',
            'no_kk' => '3273010101010006',
            'nik' => '3273010202020006',
            'metode_verifikasi' => 'tatap_muka',
        ])
        ->assertRedirect(route('verifikasi-warga.index'));

    $this->actingAs($warga->user->fresh())
        ->get(route('tagihan.index'))
        ->assertOk();
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
