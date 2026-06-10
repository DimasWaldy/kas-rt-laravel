<?php

use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('pengaduan photo is stored privately and served through controller', function () {
    Storage::fake('local');
    Storage::fake('public');

    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);
    $warga = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($warga)->post(route('pengaduan.store'), [
        '_form' => 'pengaduan',
        'judul' => 'Lampu jalan mati',
        'kategori' => 'Keamanan',
        'deskripsi' => 'Lampu jalan depan pos ronda mati sejak semalam.',
        'foto' => UploadedFile::fake()->image('lampu-jalan.jpg'),
    ])->assertRedirect('/pengaduan');

    $pengaduan = Pengaduan::firstOrFail();

    expect($pengaduan->foto)->not->toBeNull();
    Storage::disk('local')->assertExists($pengaduan->foto);
    Storage::disk('public')->assertMissing($pengaduan->foto);

    $this->actingAs($warga)
        ->get(route('pengaduan.foto', $pengaduan))
        ->assertOk();
});
