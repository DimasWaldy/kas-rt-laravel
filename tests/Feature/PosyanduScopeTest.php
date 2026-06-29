<?php

use App\Models\Balita;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\WhoGrowthStandard;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\WhoGrowthStandardSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(WhoGrowthStandardSeeder::class);

    $this->rw = Rw::create(['name' => 'RW 05', 'is_active' => true]);
    $this->rtSatu = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->rtDua = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 02', 'is_active' => true]);

    $makeUser = fn (string $role, ?int $rtId = null) => User::factory()->create([
        'role_id' => Role::where('name', $role)->value('id'),
        'rt_id' => $rtId,
    ]);

    $this->sekretarisRt = $makeUser('sekretaris_rt', $this->rtSatu->id);
    $this->ketuaRt = $makeUser('ketua_rt', $this->rtSatu->id);
    $this->wargaSatu = $makeUser('warga', $this->rtSatu->id);
    $this->wargaDua = $makeUser('warga', $this->rtDua->id);
    $this->petugas = $makeUser('petugas_posyandu');
});

test('pengurus RT membuat balita hanya di RT sendiri', function () {
    $response = $this->actingAs($this->sekretarisRt)->post(route('posyandu.store'), [
        'rt_id' => $this->rtDua->id,
        'orang_tua_id' => $this->wargaSatu->id,
        'nama' => 'Balita RT Satu',
        'jenis_kelamin' => 'perempuan',
        'tanggal_lahir' => today()->subYear()->toDateString(),
        'is_active' => true,
    ]);

    $balita = Balita::where('nama', 'Balita RT Satu')->firstOrFail();

    $response->assertRedirect(route('posyandu.show', $balita));
    expect($balita->rt_id)->toBe($this->rtSatu->id)
        ->and($balita->rw_id)->toBe($this->rw->id);
});

test('orang tua balita wajib warga dari RT yang sama', function () {
    $response = $this->actingAs($this->sekretarisRt)
        ->from(route('posyandu.create'))
        ->post(route('posyandu.store'), [
            'orang_tua_id' => $this->wargaDua->id,
            'nama' => 'Data Salah Wilayah',
            'jenis_kelamin' => 'laki_laki',
            'tanggal_lahir' => today()->subYear()->toDateString(),
        ]);

    $response->assertRedirect(route('posyandu.create'))
        ->assertSessionHasErrors('orang_tua_id');
    $this->assertDatabaseMissing('balitas', ['nama' => 'Data Salah Wilayah']);
});

test('petugas posyandu dapat mencatat pemeriksaan lintas RT dalam RW aktif', function () {
    $balita = Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtDua->id,
        'orang_tua_id' => $this->wargaDua->id,
        'nama' => 'Balita RT Dua',
        'jenis_kelamin' => 'laki_laki',
        'tanggal_lahir' => '2025-06-24',
        'is_active' => true,
    ]);
    $median = WhoGrowthStandard::weightForAge()
        ->forGender('laki_laki')
        ->where('usia_bulan', 12)
        ->value('m');

    $response = $this->actingAs($this->petugas)->post(
        route('posyandu.pemeriksaan.store', $balita),
        [
            'tanggal_pemeriksaan' => '2026-06-24',
            'berat_kg' => $median,
            'panjang_tinggi_cm' => 75,
            'metode_ukur_tinggi' => 'terlentang',
        ]
    );

    $response->assertRedirect(route('posyandu.show', $balita));
    $this->assertDatabaseHas('pemeriksaan_posyandus', [
        'balita_id' => $balita->id,
        'petugas_id' => $this->petugas->id,
        'usia_bulan' => 12,
        'z_score_bb_u' => 0,
        'status_bb_u' => 'normal',
    ]);
});

test('pengurus RT tidak dapat membuka data balita RT lain', function () {
    $balita = Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtDua->id,
        'orang_tua_id' => $this->wargaDua->id,
        'nama' => 'Balita Tersembunyi',
        'jenis_kelamin' => 'perempuan',
        'tanggal_lahir' => today()->subYear(),
        'is_active' => true,
    ]);

    $this->actingAs($this->ketuaRt)
        ->get(route('posyandu.show', $balita))
        ->assertForbidden();
    $this->actingAs($this->sekretarisRt)
        ->get(route('posyandu.edit', $balita))
        ->assertForbidden();
});

test('role tanpa permission tidak dapat menggunakan endpoint pengelolaan dan pemeriksaan', function () {
    $balita = Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtSatu->id,
        'orang_tua_id' => $this->wargaSatu->id,
        'nama' => 'Balita Warga',
        'jenis_kelamin' => 'laki_laki',
        'tanggal_lahir' => today()->subYear(),
        'is_active' => true,
    ]);

    $this->actingAs($this->wargaSatu)
        ->post(route('posyandu.store'), [])
        ->assertForbidden();
    $this->actingAs($this->ketuaRt)
        ->post(route('posyandu.pemeriksaan.store', $balita), [])
        ->assertForbidden();
});

test('halaman daftar mengikuti scope pengguna dan dapat dirender', function () {
    $milikWargaSatu = Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtSatu->id,
        'orang_tua_id' => $this->wargaSatu->id,
        'nama' => 'Anak Milik Warga Satu',
        'jenis_kelamin' => 'perempuan',
        'tanggal_lahir' => today()->subYear(),
        'is_active' => true,
    ]);
    Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtDua->id,
        'orang_tua_id' => $this->wargaDua->id,
        'nama' => 'Anak Milik Warga Dua',
        'jenis_kelamin' => 'laki_laki',
        'tanggal_lahir' => today()->subYear(),
        'is_active' => true,
    ]);

    $this->actingAs($this->wargaSatu)
        ->get(route('posyandu.index'))
        ->assertOk()
        ->assertSee($milikWargaSatu->nama)
        ->assertDontSee('Anak Milik Warga Dua');

    $this->actingAs($this->petugas)
        ->get(route('posyandu.index'))
        ->assertOk()
        ->assertSee('Anak Milik Warga Satu')
        ->assertSee('Anak Milik Warga Dua');
});

test('detail balita menampilkan kurva kms WHO dan form pemeriksaan untuk petugas', function () {
    $balita = Balita::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rtSatu->id,
        'orang_tua_id' => $this->wargaSatu->id,
        'nama' => 'Balita KMS',
        'jenis_kelamin' => 'perempuan',
        'tanggal_lahir' => today()->subMonths(10),
        'is_active' => true,
    ]);

    $this->actingAs($this->petugas)
        ->get(route('posyandu.show', $balita))
        ->assertOk()
        ->assertSee('Kurva Berat Badan Menurut Umur')
        ->assertSee('Grafik KMS berat badan menurut umur', false)
        ->assertSee('Simpan & Hitung Z-score', false)
        ->assertSee('Penilaian stunting', false);
});
