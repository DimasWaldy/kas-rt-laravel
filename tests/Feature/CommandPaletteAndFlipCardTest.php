<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->rw = Rw::create(['name' => 'RW Test', 'is_active' => true]);
    $this->rt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->rumah = Rumah::create(['kode_rumah' => 'A-01', 'rt_id' => $this->rt->id, 'status' => 'aktif']);

    $this->wargaUser = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id'),
        'rt_id' => $this->rt->id,
        'rumah_id' => $this->rumah->id,
        'status_akun' => 'aktif',
        'onboarding_domisili' => false,
    ]);

    Warga::create([
        'user_id' => $this->wargaUser->id,
        'nama_lengkap' => $this->wargaUser->name,
        'status_dalam_kk' => 'kepala_keluarga',
        'status_verifikasi' => 'terverifikasi',
    ]);

    $this->ketuaRt = User::factory()->create([
        'role_id' => Role::where('name', 'ketua_rt')->value('id'),
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);

    $this->bendahara = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara')->value('id'),
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);
});

test('halaman kartu rfid menampilkan komponen 3d flip card dengan sisi depan dan belakang', function () {
    $response = $this->actingAs($this->wargaUser)->get(route('warga.rfid.show'));

    $response->assertOk();
    $response->assertSee('perspective-1000');
    $response->assertSee('transform-style-3d');
    $response->assertSee('backface-hidden');
    $response->assertSee('rotate-y-180');
    $response->assertSee('Putar Kartu 3D (Lihat Sisi Belakang)');
    $response->assertSee('UID Chip Sensor');
    $response->assertSee('SMART-RW-SECURE-ID');
    $response->assertSee('Smart RW Citizen ID');
    $response->assertSee('CONTACTLESS NFC / RFID');
});

test('layout utama memuat trigger command palette ctrl k dan aksi cepat untuk pengurus', function () {
    $response = $this->actingAs($this->ketuaRt)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('commandPalette(');
    $response->assertSee('Ctrl K');
    $response->assertSee('Cari menu / aksi...');
    $response->assertSee('Ketik untuk mencari menu atau aksi pengurus...');
    $response->assertSee('Ganti Mode Tampilan (Terang atau Gelap)');
    $response->assertSee('Buka Sesi Alat Verifikasi KTP (RFID)');

    $bendaharaResponse = $this->actingAs($this->bendahara)->get(route('dashboard'));
    $bendaharaResponse->assertOk();
    $bendaharaResponse->assertSee('Catat Kas Masuk Baru');
});
