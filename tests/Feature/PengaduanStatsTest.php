<?php

use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
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

test('pengaduan status update allows foto_selesai and displays before after slider', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('local');

    $rw = Rw::create(['name' => 'RW 01', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01', 'is_active' => true]);

    $adminRole = Role::where('name', 'ketua_rt')->first();
    $wargaRole = Role::where('name', 'warga')->first();

    $admin = User::factory()->create(['role_id' => $adminRole->id, 'rt_id' => $rt->id]);
    $warga = User::factory()->create(['role_id' => $wargaRole->id, 'rt_id' => $rt->id]);

    $fotoAwal = UploadedFile::fake()->image('laporan-rusak.jpg')->store('pengaduan', 'local');

    $pengaduan = Pengaduan::create([
        'user_id' => $warga->id,
        'judul' => 'Jalan paving hancur',
        'kategori' => 'Infrastruktur',
        'deskripsi' => 'Paving blok hancur parah dan berlubang.',
        'foto' => $fotoAwal,
        'status' => 'proses',
    ]);

    $this->actingAs($admin)
        ->patch(route('pengaduan.status', $pengaduan), [
            'status' => 'selesai',
            'tanggapan' => 'Sudah dilakukan perbaikan paving blok.',
            'foto_selesai' => UploadedFile::fake()->image('laporan-rapi.jpg'),
        ])
        ->assertRedirect(route('pengaduan.show', $pengaduan))
        ->assertSessionHas('success');

    $pengaduanFresh = $pengaduan->fresh();
    expect($pengaduanFresh->status)->toBe('selesai')
        ->and($pengaduanFresh->foto_selesai)->not->toBeNull();

    Storage::disk('local')->assertExists($pengaduanFresh->foto_selesai);

    // Endpoint foto-selesai dapat diakses
    $this->actingAs($warga)
        ->get(route('pengaduan.foto-selesai', $pengaduanFresh))
        ->assertOk();

    // Halaman show menampilkan before / after comparison slider
    $this->actingAs($warga)
        ->get(route('pengaduan.show', $pengaduanFresh))
        ->assertOk()
        ->assertSee('Perbandingan Sebelum &amp; Sesudah', false)
        ->assertSee('viewMode === \'slider\'', false)
        ->assertSee(route('pengaduan.foto', $pengaduanFresh), false)
        ->assertSee(route('pengaduan.foto-selesai', $pengaduanFresh), false);
});
