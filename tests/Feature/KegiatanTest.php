<?php

use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW 05',
        'address' => 'Jl. Kegiatan Warga No. 5',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rt = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $makeUser = function (string $role, ?Rt $rt = null): User {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'rt_id' => $rt?->id,
        ]);
    };

    $this->ketuaRw = $makeUser('ketua_rw');
    $this->warga = $makeUser('warga', $this->rt);

    $this->makeKegiatan = function (array $attributes = []): Kegiatan {
        return Kegiatan::create(array_merge([
            'rw_id' => $this->rw->id,
            'created_by' => $this->ketuaRw->id,
            'nama' => 'Kerja Bakti RW',
            'deskripsi' => 'Kerja bakti bersama seluruh warga RW.',
            'tanggal_mulai' => now()->addWeek(),
            'tanggal_selesai' => now()->addWeek()->addHours(3),
            'lokasi' => 'Balai RW',
            'estimasi_biaya' => 1000000,
            'realisasi_biaya' => 0,
            'status' => 'akan_datang',
        ], $attributes));
    };
});

test('pengurus rw dapat membuat kegiatan dengan foto', function () {
    Storage::fake('local');

    $response = $this->actingAs($this->ketuaRw)->post(route('kegiatan.store'), [
        'nama' => 'Senam Bersama RW',
        'deskripsi' => 'Senam pagi bersama warga lintas RT.',
        'tanggal_mulai' => now()->addDays(7)->format('Y-m-d H:i:s'),
        'tanggal_selesai' => now()->addDays(7)->addHours(2)->format('Y-m-d H:i:s'),
        'lokasi' => 'Lapangan RW',
        'foto' => UploadedFile::fake()->image('senam-rw.jpg'),
        'estimasi_biaya' => 750000,
        'realisasi_biaya' => 0,
    ]);

    $kegiatan = Kegiatan::firstOrFail();

    $response->assertRedirect(route('kegiatan.show', $kegiatan));
    $this->assertDatabaseHas('kegiatans', [
        'id' => $kegiatan->id,
        'rw_id' => $this->rw->id,
        'created_by' => $this->ketuaRw->id,
        'nama' => 'Senam Bersama RW',
        'status' => 'akan_datang',
    ]);
    Storage::disk('local')->assertExists($kegiatan->foto);
});

test('warga dapat melihat kegiatan dan konfirmasi hadir', function () {
    $kegiatan = ($this->makeKegiatan)();

    $this->actingAs($this->warga)
        ->get(route('kegiatan.index'))
        ->assertOk()
        ->assertSee($kegiatan->nama);

    $this->actingAs($this->warga)
        ->post(route('kegiatan.hadir', $kegiatan), [
            'catatan' => 'Saya hadir bersama keluarga.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('kegiatan_hadirs', [
        'kegiatan_id' => $kegiatan->id,
        'user_id' => $this->warga->id,
        'catatan' => 'Saya hadir bersama keluarga.',
    ]);
});

test('konfirmasi hadir warga kedua kali memperbarui record yang sama', function () {
    $kegiatan = ($this->makeKegiatan)();

    $this->actingAs($this->warga)
        ->post(route('kegiatan.hadir', $kegiatan), ['catatan' => 'Konfirmasi pertama'])
        ->assertRedirect();

    $this->actingAs($this->warga)
        ->post(route('kegiatan.hadir', $kegiatan), ['catatan' => 'Konfirmasi diperbarui'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(KegiatanHadir::where('kegiatan_id', $kegiatan->id)
        ->where('user_id', $this->warga->id)
        ->count())->toBe(1);

    $this->assertDatabaseHas('kegiatan_hadirs', [
        'kegiatan_id' => $kegiatan->id,
        'user_id' => $this->warga->id,
        'catatan' => 'Konfirmasi diperbarui',
    ]);
});

test('warga tidak dapat membuat kegiatan', function () {
    $this->actingAs($this->warga)
        ->post(route('kegiatan.store'), [
            'nama' => 'Kegiatan Tidak Sah',
            'tanggal_mulai' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('kegiatans', [
        'nama' => 'Kegiatan Tidak Sah',
    ]);
});

test('kegiatan yang dibatalkan tidak dapat dikonfirmasi hadir', function () {
    $kegiatan = ($this->makeKegiatan)([
        'status' => 'dibatalkan',
        'catatan_pembatalan' => 'Kondisi lokasi tidak memungkinkan.',
    ]);

    $this->actingAs($this->warga)
        ->post(route('kegiatan.hadir', $kegiatan))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('kegiatan_hadirs', [
        'kegiatan_id' => $kegiatan->id,
        'user_id' => $this->warga->id,
    ]);
});

test('foto kegiatan disimpan di local disk dan diakses melalui controller', function () {
    Storage::fake('local');

    $this->actingAs($this->ketuaRw)->post(route('kegiatan.store'), [
        'nama' => 'Festival Warga',
        'tanggal_mulai' => now()->addMonth()->format('Y-m-d H:i:s'),
        'foto' => UploadedFile::fake()->image('festival.jpg', 800, 600),
    ])->assertRedirect();

    $kegiatan = Kegiatan::firstOrFail();

    expect($kegiatan->foto)->toStartWith('kegiatan/');
    Storage::disk('local')->assertExists($kegiatan->foto);

    $this->actingAs($this->warga)
        ->get(route('kegiatan.foto', $kegiatan))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});
