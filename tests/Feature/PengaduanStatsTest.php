<?php

use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\User;

test('pengaduan index calculates status stats from grouped query result', function () {
    $wargaRole = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $warga = User::factory()->create([
        'role_id' => $wargaRole->id,
    ]);

    Pengaduan::create([
        'user_id' => $warga->id,
        'judul' => 'Lampu jalan mati',
        'kategori' => 'Infrastruktur',
        'deskripsi' => 'Lampu jalan depan rumah mati.',
        'status' => 'pending',
    ]);

    Pengaduan::create([
        'user_id' => $warga->id,
        'judul' => 'Sampah menumpuk',
        'kategori' => 'Kebersihan',
        'deskripsi' => 'Sampah menumpuk di dekat pos.',
        'status' => 'proses',
    ]);

    Pengaduan::create([
        'user_id' => $warga->id,
        'judul' => 'Jalan berlubang',
        'kategori' => 'Infrastruktur',
        'deskripsi' => 'Jalan utama berlubang.',
        'status' => 'selesai',
    ]);

    Pengaduan::create([
        'user_id' => $warga->id,
        'judul' => 'Aduan arsip',
        'kategori' => 'Lainnya',
        'deskripsi' => 'Aduan sudah ditolak.',
        'status' => 'ditolak',
    ]);

    $response = $this->actingAs($warga)->get(route('pengaduan.index'));

    $response->assertOk();
    $response->assertViewHas('stats', [
        'total' => 4,
        'pending' => 1,
        'proses' => 1,
        'selesai' => 1,
    ]);
});
