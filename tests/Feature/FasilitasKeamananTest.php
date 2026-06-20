<?php

use App\Models\Fasilitas;
use App\Models\JadwalShiftSatpam;
use App\Models\LogPatroli;
use App\Models\PengaduanFasilitas;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Fasilitas',
        'address' => 'Jl. Aman No. 1',
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

    $this->ketuaRt = User::factory()->create([
        'role_id' => $roleId('ketua_rt'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->ketuaRw = User::factory()->create([
        'role_id' => $roleId('ketua_rw'),
        'rt_id' => null,
    ]);

    $this->bendaharaRw = User::factory()->create([
        'role_id' => $roleId('bendahara_rw'),
        'rt_id' => null,
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaRtDua = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtDua->id,
    ]);

    $this->buatFasilitas = function (array $attributes = []): Fasilitas {
        return Fasilitas::create(array_merge([
            'rw_id' => $this->rw->id,
            'rt_id' => $this->rtSatu->id,
            'nama' => 'Lampu Jalan RT 01',
            'kategori' => 'penerangan',
            'lokasi_blok' => 'Blok A',
            'lokasi_deskripsi' => 'Dekat pos ronda RT 01.',
            'kondisi' => 'baik',
            'is_active' => true,
        ], $attributes));
    };
});

afterEach(function () {
    Carbon::setTestNow();
});

test('pengurus rt dapat menambah fasilitas untuk rt sendiri', function () {
    Storage::fake('local');

    $response = $this->actingAs($this->ketuaRt)
        ->post(route('fasilitas.store'), [
            'nama' => 'CCTV Gerbang RT',
            'kategori' => 'cctv',
            'lokasi_blok' => 'Gerbang RT 01',
            'lokasi_deskripsi' => 'Mengarah ke jalan utama.',
            'kondisi' => 'baik',
            'foto' => UploadedFile::fake()->image('cctv.jpg', 800, 600),
            'catatan' => 'Perlu dicek tiap bulan.',
            'is_active' => '1',
        ]);

    $fasilitas = Fasilitas::firstOrFail();

    $response->assertRedirect(route('fasilitas.show', $fasilitas));
    $this->assertDatabaseHas('fasilitas', [
        'id' => $fasilitas->id,
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtSatu->id,
        'nama' => 'CCTV Gerbang RT',
        'kategori' => 'cctv',
    ]);
    Storage::disk('local')->assertExists($fasilitas->foto);
});

test('warga hanya melihat fasilitas rw dan fasilitas rt sendiri', function () {
    $fasilitasRtSatu = ($this->buatFasilitas)(['nama' => 'Lampu RT 01']);
    $fasilitasRw = ($this->buatFasilitas)([
        'rt_id' => null,
        'nama' => 'CCTV Gerbang RW',
        'kategori' => 'cctv',
    ]);
    $fasilitasRtDua = ($this->buatFasilitas)([
        'rt_id' => $this->rtDua->id,
        'nama' => 'Lampu RT 02',
    ]);

    $this->actingAs($this->warga)
        ->get(route('fasilitas.index'))
        ->assertOk()
        ->assertSee($fasilitasRtSatu->nama)
        ->assertSee($fasilitasRw->nama)
        ->assertDontSee($fasilitasRtDua->nama);

    $this->actingAs($this->warga)
        ->get(route('fasilitas.show', $fasilitasRtDua))
        ->assertForbidden();
});

test('pengurus rw dapat melihat fasilitas lintas rt', function () {
    $fasilitasRtSatu = ($this->buatFasilitas)(['nama' => 'Lampu RT 01']);
    $fasilitasRtDua = ($this->buatFasilitas)([
        'rt_id' => $this->rtDua->id,
        'nama' => 'Lampu RT 02',
    ]);

    $this->actingAs($this->ketuaRw)
        ->get(route('fasilitas.index'))
        ->assertOk()
        ->assertSee($fasilitasRtSatu->nama)
        ->assertSee($fasilitasRtDua->nama);
});

test('warga dapat membuat pengaduan fasilitas dan kondisi fasilitas diperbarui', function () {
    Storage::fake('local');
    $fasilitas = ($this->buatFasilitas)();

    $response = $this->actingAs($this->warga)
        ->post(route('pengaduan-fasilitas.store'), [
            'fasilitas_id' => $fasilitas->id,
            'jenis_masalah' => 'mati',
            'deskripsi' => 'Lampu jalan mati sejak tadi malam dan area menjadi gelap.',
            'foto' => UploadedFile::fake()->image('lampu-mati.jpg', 800, 600),
        ]);

    $pengaduan = PengaduanFasilitas::firstOrFail();

    $response->assertRedirect(route('pengaduan-fasilitas.show', $pengaduan));
    $this->assertDatabaseHas('pengaduan_fasilitas', [
        'id' => $pengaduan->id,
        'fasilitas_id' => $fasilitas->id,
        'pelapor_id' => $this->warga->id,
        'jenis_masalah' => 'mati',
        'status' => 'dilaporkan',
    ]);
    expect($fasilitas->fresh()->kondisi)->toBe('perlu_perhatian');
    Storage::disk('local')->assertExists($pengaduan->foto);
});

test('warga tidak bisa melaporkan fasilitas rt lain', function () {
    $fasilitasRtDua = ($this->buatFasilitas)([
        'rt_id' => $this->rtDua->id,
        'nama' => 'CCTV RT 02',
    ]);

    $this->actingAs($this->warga)
        ->post(route('pengaduan-fasilitas.store'), [
            'fasilitas_id' => $fasilitasRtDua->id,
            'jenis_masalah' => 'rusak',
            'deskripsi' => 'Fasilitas ini tidak boleh bisa dilaporkan warga RT lain.',
        ])
        ->assertForbidden();

    expect(PengaduanFasilitas::count())->toBe(0);
});

test('pengurus dapat tindak lanjut dan menyelesaikan pengaduan fasilitas', function () {
    $fasilitas = ($this->buatFasilitas)(['kondisi' => 'perlu_perhatian']);
    $pengaduan = PengaduanFasilitas::create([
        'fasilitas_id' => $fasilitas->id,
        'pelapor_id' => $this->warga->id,
        'jenis_masalah' => 'rusak',
        'deskripsi' => 'Penutup box CCTV rusak.',
        'status' => 'dilaporkan',
    ]);

    $this->actingAs($this->ketuaRt)
        ->patch(route('pengaduan-fasilitas.tindak-lanjut', $pengaduan), [
            'catatan_tindak_lanjut' => 'Akan dicek petugas hari ini.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pengaduan->fresh()->status)->toBe('ditindaklanjuti')
        ->and($pengaduan->fresh()->ditindaklanjuti_oleh)->toBe($this->ketuaRt->id);

    $this->actingAs($this->ketuaRt)
        ->patch(route('pengaduan-fasilitas.selesai', $pengaduan), [
            'catatan_tindak_lanjut' => 'Box sudah diperbaiki.',
            'update_kondisi_baik' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pengaduan->fresh()->status)->toBe('selesai')
        ->and($pengaduan->fresh()->tanggal_selesai)->not->toBeNull()
        ->and($fasilitas->fresh()->kondisi)->toBe('baik');
});

test('role tanpa manage fasilitas tidak bisa mengakses dashboard keamanan', function () {
    $this->actingAs($this->bendaharaRw)
        ->get(route('keamanan.index'))
        ->assertForbidden();

    $this->actingAs($this->warga)
        ->post(route('keamanan.shift.store'), [
            'nama_satpam' => 'Pak Aman',
            'shift' => 'pagi',
            'jam_mulai' => '06:00',
            'jam_selesai' => '14:00',
            'tanggal' => now()->toDateString(),
        ])
        ->assertForbidden();
});

test('pengurus rw dapat membuat shift satpam dan mencatat log patroli', function () {
    $response = $this->actingAs($this->ketuaRw)
        ->post(route('keamanan.shift.store'), [
            'nama_satpam' => 'Pak Aman',
            'kontak_satpam' => '08123456789',
            'shift' => 'malam',
            'jam_mulai' => '22:00',
            'jam_selesai' => '06:00',
            'tanggal' => now()->toDateString(),
        ]);

    $shift = JadwalShiftSatpam::firstOrFail();

    $response->assertRedirect(route('keamanan.shift.show', $shift));
    $this->assertDatabaseHas('jadwal_shift_satpams', [
        'id' => $shift->id,
        'rw_id' => $this->rw->id,
        'nama_satpam' => 'Pak Aman',
        'dicatat_oleh' => $this->ketuaRw->id,
    ]);

    $this->actingAs($this->ketuaRw)
        ->post(route('keamanan.patroli.store', $shift), [
            'waktu_patroli' => now()->format('Y-m-d H:i:s'),
            'catatan' => 'Patroli gerbang utama, kondisi aman.',
            'ada_kejadian' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('log_patrolis', [
        'jadwal_shift_id' => $shift->id,
        'catatan' => 'Patroli gerbang utama, kondisi aman.',
        'ada_kejadian' => true,
        'dicatat_oleh' => $this->ketuaRw->id,
    ]);
    expect(LogPatroli::count())->toBe(1);
});
