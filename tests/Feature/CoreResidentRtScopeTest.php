<?php

use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $rw = Rw::create([
        'name' => 'RW Scope Warga',
        'is_active' => true,
    ]);

    $this->rtSatu = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rtDua = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $this->sekretarisRtSatu = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris')->value('id'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $wargaRoleId = Role::where('name', 'warga')->value('id');

    $this->wargaRtSatu = User::factory()->create([
        'name' => 'Warga RT Satu',
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaRtDua = User::factory()->create([
        'name' => 'Warga RT Dua',
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtDua->id,
    ]);

    $this->rumahRtSatu = Rumah::create([
        'kode_rumah' => 'RT1-A01',
        'rt_id' => $this->rtSatu->id,
        'status' => 'aktif',
    ]);

    $this->rumahRtDua = Rumah::create([
        'kode_rumah' => 'RT2-A01',
        'rt_id' => $this->rtDua->id,
        'status' => 'aktif',
    ]);
});

test('sekretaris rt only manages residents and houses from their own rt', function () {
    $this->actingAs($this->sekretarisRtSatu)
        ->get(route('admin.warga.index'))
        ->assertOk()
        ->assertSee('Warga RT Satu')
        ->assertDontSee('Warga RT Dua');

    $this->actingAs($this->sekretarisRtSatu)
        ->get(route('admin.warga.edit', $this->wargaRtDua))
        ->assertNotFound();

    $this->actingAs($this->sekretarisRtSatu)
        ->get(route('admin.rumah.index'))
        ->assertOk()
        ->assertSee('RT1-A01')
        ->assertDontSee('RT2-A01');

    $this->actingAs($this->sekretarisRtSatu)
        ->get(route('admin.rumah.show', $this->rumahRtDua))
        ->assertNotFound();
});

test('new resident created by rt secretary inherits their rt', function () {
    $this->actingAs($this->sekretarisRtSatu)
        ->post(route('admin.warga.store'), [
            'name' => 'Warga Baru RT Satu',
            'email' => 'warga-baru-rt1@example.com',
            'password' => 'password',
            'no_kk' => '3174000000000001',
            'phone' => '081234567890',
        ])
        ->assertRedirect(route('admin.warga.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'warga-baru-rt1@example.com',
        'rt_id' => $this->rtSatu->id,
    ]);
});

test('rt secretary cannot move resident into a house from another rt', function () {
    $this->wargaRtSatu->update(['rumah_id' => $this->rumahRtSatu->id]);

    $this->actingAs($this->sekretarisRtSatu)
        ->post(route('admin.rumah.warga.move', [$this->rumahRtSatu, $this->wargaRtSatu]), [
            'target_rumah_id' => $this->rumahRtDua->id,
        ])
        ->assertSessionHasErrors('target_rumah_id');

    expect($this->wargaRtSatu->fresh()->rumah_id)->toBe($this->rumahRtSatu->id);
    expect($this->wargaRtSatu->fresh()->rt_id)->toBe($this->rtSatu->id);
});

test('complaints and complaint actions are isolated by rt', function () {
    $pengaduanRtSatu = Pengaduan::create([
        'user_id' => $this->wargaRtSatu->id,
        'judul' => 'Aduan RT Satu',
        'kategori' => 'Keamanan',
        'deskripsi' => 'Aduan hanya untuk RT satu.',
        'status' => 'pending',
    ]);

    $pengaduanRtDua = Pengaduan::create([
        'user_id' => $this->wargaRtDua->id,
        'judul' => 'Aduan RT Dua',
        'kategori' => 'Kebersihan',
        'deskripsi' => 'Aduan hanya untuk RT dua.',
        'status' => 'pending',
    ]);

    $this->actingAs($this->wargaRtSatu)
        ->get(route('pengaduan.index'))
        ->assertOk()
        ->assertSee('Aduan RT Satu')
        ->assertDontSee('Aduan RT Dua')
        ->assertViewHas('stats', [
            'total' => 1,
            'pending' => 1,
            'proses' => 0,
            'selesai' => 0,
        ]);

    $this->actingAs($this->sekretarisRtSatu)
        ->get(route('pengaduan.show', $pengaduanRtDua))
        ->assertNotFound();

    $this->actingAs($this->sekretarisRtSatu)
        ->patch(route('pengaduan.status', $pengaduanRtDua), [
            'status' => 'proses',
            'tanggapan' => 'Sedang kami tindak lanjuti.',
        ])
        ->assertNotFound();

    $this->actingAs($this->sekretarisRtSatu)
        ->patch(route('pengaduan.status', $pengaduanRtSatu), [
            'status' => 'proses',
            'tanggapan' => 'Sedang kami tindak lanjuti.',
        ])
        ->assertRedirect(route('pengaduan.show', $pengaduanRtSatu));

    expect($pengaduanRtSatu->fresh()->status)->toBe('proses');
    expect($pengaduanRtDua->fresh()->status)->toBe('pending');
});
