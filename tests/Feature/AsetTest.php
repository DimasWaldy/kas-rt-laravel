<?php

use App\Models\Aset;
use App\Models\PeminjamanAset;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-16 09:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Test',
        'address' => 'Jl. Aset Warga No. 5',
        'kota' => 'Bandung',
        'is_active' => true,
    ]);

    $this->rtSatu = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rtDua = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->pengurus = User::factory()->create([
        'role_id' => $roleId('ketua_rt'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaRtDua = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtDua->id,
    ]);

    $this->buatAset = function (array $attributes = []): Aset {
        return Aset::create(array_merge([
            'rt_id' => $this->rtSatu->id,
            'nama' => 'Tenda Peleton',
            'kategori' => 'tenda_dan_terpal',
            'deskripsi' => 'Tenda untuk kegiatan warga.',
            'jumlah_total' => 10,
            'kondisi' => 'baik',
            'nilai_perkiraan' => 2500000,
            'tanggal_pengadaan' => now()->subMonth()->toDateString(),
            'lokasi_penyimpanan' => 'Gudang RT',
            'is_active' => true,
        ], $attributes));
    };

    $this->buatPeminjaman = function (Aset $aset, array $attributes = []): PeminjamanAset {
        return PeminjamanAset::create(array_merge([
            'aset_id' => $aset->id,
            'pemohon_id' => $this->warga->id,
            'tanggal_mulai' => now()->addDays(4)->toDateString(),
            'tanggal_selesai' => now()->addDays(6)->toDateString(),
            'keperluan' => 'Kegiatan warga RT',
            'jumlah_dipinjam' => 1,
            'status' => 'diajukan',
        ], $attributes));
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('pengurus rt dapat menambah aset baru dengan foto', function () {
    Storage::fake('local');

    $response = $this->actingAs($this->pengurus)
        ->post(route('aset.store'), [
            'nama' => 'Kursi Plastik',
            'kategori' => 'furniture',
            'deskripsi' => 'Kursi untuk rapat warga.',
            'jumlah_total' => 25,
            'kondisi' => 'baik',
            'nilai_perkiraan' => 1250000,
            'tanggal_pengadaan' => now()->toDateString(),
            'lokasi_penyimpanan' => 'Balai RT',
            'foto' => UploadedFile::fake()->image('kursi.jpg', 800, 600),
        ]);

    $aset = Aset::firstOrFail();

    $response->assertRedirect(route('aset.show', $aset));
    $this->assertDatabaseHas('asets', [
        'id' => $aset->id,
        'rt_id' => $this->rtSatu->id,
        'nama' => 'Kursi Plastik',
        'kategori' => 'furniture',
        'jumlah_total' => 25,
    ]);
    expect($aset->foto)->toStartWith('aset/');
    Storage::disk('local')->assertExists($aset->foto);
});

test('warga dapat melihat aset dan mengajukan peminjaman', function () {
    $aset = ($this->buatAset)();

    $this->actingAs($this->warga)
        ->get(route('aset.index'))
        ->assertOk()
        ->assertSee($aset->nama);

    $response = $this->actingAs($this->warga)
        ->post(route('peminjaman-aset.store'), [
            'aset_id' => $aset->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'keperluan' => 'Rapat warga RT',
            'jumlah_dipinjam' => 1,
            'catatan_pemohon' => 'Akan diambil pagi hari.',
        ]);

    $peminjaman = PeminjamanAset::firstOrFail();

    $response->assertRedirect(route('peminjaman-aset.show', $peminjaman));
    $this->assertDatabaseHas('peminjaman_asets', [
        'id' => $peminjaman->id,
        'aset_id' => $aset->id,
        'pemohon_id' => $this->warga->id,
        'status' => 'diajukan',
    ]);
});

test('sistem menolak peminjaman jika tanggal konflik', function () {
    $aset = ($this->buatAset)();
    ($this->buatPeminjaman)($aset, [
        'tanggal_mulai' => now()->addDays(4)->toDateString(),
        'tanggal_selesai' => now()->addDays(9)->toDateString(),
        'status' => 'disetujui',
    ]);

    $this->actingAs($this->warga)
        ->post(route('peminjaman-aset.store'), [
            'aset_id' => $aset->id,
            'tanggal_mulai' => now()->addDays(6)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'keperluan' => 'Acara keluarga warga',
            'jumlah_dipinjam' => 1,
        ])
        ->assertSessionHasErrors('tanggal_mulai');

    expect(PeminjamanAset::count())->toBe(1);
});

test('pengurus dapat setujui konfirmasi dipinjam dan konfirmasi kembali', function () {
    $aset = ($this->buatAset)();
    $peminjaman = ($this->buatPeminjaman)($aset);

    $this->actingAs($this->pengurus)
        ->patch(route('peminjaman-aset.setujui', $peminjaman), [
            'catatan_pengurus' => 'Silakan diambil sesuai jadwal.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($peminjaman->fresh()->status)->toBe('disetujui')
        ->and($peminjaman->fresh()->diproses_oleh)->toBe($this->pengurus->id)
        ->and($peminjaman->fresh()->tanggal_diproses)->not->toBeNull();

    $this->actingAs($this->pengurus)
        ->patch(route('peminjaman-aset.dipinjam', $peminjaman))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($peminjaman->fresh()->status)->toBe('dipinjam')
        ->and($peminjaman->fresh()->tanggal_dipinjam)->not->toBeNull();

    $this->actingAs($this->pengurus)
        ->patch(route('peminjaman-aset.kembali', $peminjaman))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($peminjaman->fresh()->status)->toBe('dikembalikan')
        ->and($peminjaman->fresh()->tanggal_dikembalikan)->not->toBeNull();
});

test('pengurus dapat tolak peminjaman dengan alasan', function () {
    $aset = ($this->buatAset)();
    $peminjaman = ($this->buatPeminjaman)($aset);

    $this->actingAs($this->pengurus)
        ->patch(route('peminjaman-aset.tolak', $peminjaman), [
            'catatan_pengurus' => 'Aset sedang disiapkan untuk kegiatan RT.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $peminjaman->refresh();

    expect($peminjaman->status)->toBe('ditolak')
        ->and($peminjaman->catatan_pengurus)->toBe('Aset sedang disiapkan untuk kegiatan RT.')
        ->and($peminjaman->diproses_oleh)->toBe($this->pengurus->id);
});

test('warga tidak bisa akses aset rt lain', function () {
    $asetRtDua = ($this->buatAset)([
        'rt_id' => $this->rtDua->id,
        'nama' => 'Sound System RT 02',
    ]);

    $this->actingAs($this->warga)
        ->get(route('aset.show', $asetRtDua))
        ->assertForbidden();
});

test('warga tidak bisa manage aset', function () {
    $this->actingAs($this->warga)
        ->post(route('aset.store'), [
            'nama' => 'Aset Tidak Sah',
            'kategori' => 'lainnya',
            'jumlah_total' => 1,
            'kondisi' => 'baik',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('asets', [
        'nama' => 'Aset Tidak Sah',
    ]);
});

test('aset yang sedang dipinjam tidak bisa dihapus', function () {
    $aset = ($this->buatAset)();
    ($this->buatPeminjaman)($aset, ['status' => 'disetujui']);

    $this->actingAs($this->pengurus)
        ->from(route('aset.show', $aset))
        ->delete(route('aset.destroy', $aset))
        ->assertRedirect(route('aset.show', $aset))
        ->assertSessionHas('error');

    expect($aset->fresh())->not->toBeNull()
        ->and($aset->fresh()->deleted_at)->toBeNull();
});

test('foto aset diakses via controller bukan url publik', function () {
    Storage::fake('local');

    $this->actingAs($this->pengurus)
        ->post(route('aset.store'), [
            'nama' => 'Genset RT',
            'kategori' => 'elektronik',
            'jumlah_total' => 1,
            'kondisi' => 'baik',
            'foto' => UploadedFile::fake()->image('genset.jpg', 800, 600),
        ])
        ->assertRedirect();

    $aset = Aset::firstOrFail();

    expect($aset->foto)->toStartWith('aset/');
    Storage::disk('local')->assertExists($aset->foto);

    $this->actingAs($this->warga)
        ->get(route('aset.foto', $aset))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});
