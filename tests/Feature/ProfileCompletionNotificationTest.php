<?php

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;

test('warga dengan profil belum lengkap melihat notifikasi lengkapi profil', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'phone' => null,
        'rumah_id' => null,
        'rt_id' => null,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profil warga belum lengkap')
        ->assertSee('Belum lengkap: Nomor HP, NIK, Nomor KK, Domisili/Rumah, RT, Status dalam KK.')
        ->assertSee('Lengkapi Profil');
});

test('warga dengan profil lengkap tidak melihat notifikasi lengkapi profil', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rw = Rw::create(['name' => 'RW Profil Lengkap']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);
    $rumah = Rumah::create([
        'kode_rumah' => 'A-01',
        'alamat' => 'Jl. Profil Lengkap',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);
    $kk = KartuKeluarga::create([
        'no_kk' => '3273000000000001',
        'rumah_id' => $rumah->id,
        'nama_kepala_keluarga' => 'Warga Lengkap',
    ]);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'phone' => '081234567890',
        'rumah_id' => $rumah->id,
        'rt_id' => $rt->id,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'kartu_keluarga_id' => $kk->id,
        'nama_lengkap' => $user->name,
        'nik' => '3273000000000002',
        'status_dalam_kk' => 'kepala_keluarga',
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Profil warga belum lengkap');
});

test('warga dapat melengkapi profil dari halaman edit sehingga notifikasi hilang', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rw = Rw::create(['name' => 'RW Update Profil']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 02']);
    $rumah = Rumah::create([
        'kode_rumah' => 'B-02',
        'alamat' => 'Jl. Update Profil',
        'rt_id' => $rt->id,
        'status' => 'aktif',
    ]);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'name' => 'Warga Update Profil',
        'email' => 'warga-update-profil@example.test',
        'phone' => null,
        'rumah_id' => null,
        'rt_id' => null,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Warga Update Profil',
            'email' => 'warga-update-profil@example.test',
            'phone' => '081234567891',
            'rumah_id' => $rumah->id,
            'nik' => '3273000000000003',
            'no_kk' => '3273000000000004',
            'status_dalam_kk' => 'kepala_keluarga',
            'is_penanggung_jawab_rumah' => '1',
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    $user->load('warga.kartuKeluarga');

    expect($user->profile_status)->toBe('Lengkap')
        ->and($user->rt_id)->toBe($rt->id)
        ->and($user->rumah_id)->toBe($rumah->id)
        ->and($user->warga->nik)->toBe('3273000000000003')
        ->and($user->warga->kartuKeluarga->no_kk)->toBe('3273000000000004');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Profil warga belum lengkap');
});

test('warga dapat memilih rt saat membuat rumah baru dari halaman edit profil', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rw = Rw::create(['name' => 'RW Rumah Baru Profil']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 03']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'name' => 'Warga Rumah Baru Profil',
        'email' => 'warga-rumah-baru-profil@example.test',
        'phone' => null,
        'rumah_id' => null,
        'rt_id' => null,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Warga Rumah Baru Profil',
            'email' => 'warga-rumah-baru-profil@example.test',
            'phone' => '081234567892',
            'rt_id' => $rt->id,
            'rumah_kode' => 'C-03',
            'rumah_alamat' => 'Jl. Rumah Baru Profil',
            'nik' => '3273000000000005',
            'no_kk' => '3273000000000006',
            'status_dalam_kk' => 'kepala_keluarga',
            'is_penanggung_jawab_rumah' => '1',
        ])
        ->assertRedirect(route('profile.edit'));

    $rumah = Rumah::where('kode_rumah', 'C-03')->firstOrFail();

    $user->refresh();
    $user->load('warga.kartuKeluarga');

    expect($rumah->rt_id)->toBe($rt->id)
        ->and($user->profile_status)->toBe('Lengkap')
        ->and($user->rt_id)->toBe($rt->id)
        ->and($user->rumah_id)->toBe($rumah->id)
        ->and($user->warga->kartuKeluarga->rumah_id)->toBe($rumah->id);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Profil warga belum lengkap');
});

test('warga dapat melengkapi rt saat memilih rumah lama yang belum punya rt', function () {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $rw = Rw::create(['name' => 'RW Rumah Lama Profil']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 04']);
    $rumah = Rumah::create([
        'kode_rumah' => 'D-04',
        'alamat' => 'Jl. Rumah Lama Profil',
        'rt_id' => null,
        'status' => 'aktif',
    ]);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'name' => 'Warga Rumah Lama Profil',
        'email' => 'warga-rumah-lama-profil@example.test',
        'phone' => null,
        'rumah_id' => null,
        'rt_id' => null,
    ]);

    Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Warga Rumah Lama Profil',
            'email' => 'warga-rumah-lama-profil@example.test',
            'phone' => '081234567893',
            'rt_id' => $rt->id,
            'rumah_id' => $rumah->id,
            'rumah_kode' => 'HARUS-DIABAIKAN',
            'rumah_alamat' => 'Alamat ini tidak boleh membuat rumah baru',
            'nik' => '3273000000000007',
            'no_kk' => '3273000000000008',
            'status_dalam_kk' => 'kepala_keluarga',
            'is_penanggung_jawab_rumah' => '1',
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    $rumah->refresh();

    expect($user->profile_status)->toBe('Lengkap')
        ->and($user->rt_id)->toBe($rt->id)
        ->and($user->rumah_id)->toBe($rumah->id)
        ->and($rumah->rt_id)->toBe($rt->id)
        ->and(Rumah::where('kode_rumah', 'HARUS-DIABAIKAN')->exists())->toBeFalse();
});
