<?php

use App\Models\Pemilihan;
use App\Models\PemilihanKandidat;
use App\Models\PemilihanSuara;
use App\Models\PemilihanVoter;
use App\Models\Permission;
use App\Models\RfidCard;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;

beforeEach(function () {
    $this->rw = Rw::create([
        'name' => '05',
        'description' => 'RW 05',
    ]);

    $this->rt = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '01',
        'description' => 'RT 01',
    ]);

    $this->rtLain = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '02',
        'description' => 'RT 02',
    ]);

    // Permissions
    $pViewVoting = Permission::firstOrCreate(['name' => 'view-voting']);
    $pSubmitVote = Permission::firstOrCreate(['name' => 'submit-vote']);
    $pManageVoting = Permission::firstOrCreate(['name' => 'manage-voting']);

    // Roles
    $this->roleWarga = Role::firstOrCreate(['name' => 'warga']);
    $this->roleWarga->permissions()->syncWithoutDetaching([$pViewVoting->id, $pSubmitVote->id]);

    $this->roleAdmin = Role::firstOrCreate(['name' => 'admin']);
    $this->roleAdmin->permissions()->syncWithoutDetaching([$pViewVoting->id, $pSubmitVote->id, $pManageVoting->id]);

    // Users
    $this->userWarga = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);

    $this->warga = Warga::create([
        'user_id' => $this->userWarga->id,
        'nama_lengkap' => $this->userWarga->name,
        'nik' => '3201010101010001',
        'no_kk' => '3201010101010001',
        'agama' => 'Islam',
        'jenis_kelamin' => 'Laki-laki',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'status_perkawinan' => 'Belum Kawin',
        'status_kependudukan' => 'Tetap',
        'kewarganegaraan' => 'WNI',
        'pekerjaan' => 'Karyawan',
        'status_verifikasi' => 'terverifikasi',
    ]);

    $this->userAdmin = User::factory()->create([
        'role_id' => $this->roleAdmin->id,
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);

    // Setup active election with 2 candidates
    $this->pemilihan = Pemilihan::create([
        'rt_id' => $this->rt->id,
        'rw_id' => $this->rw->id,
        'title' => 'Pemilihan Ketua RT 01 Periode 2026-2031',
        'slug' => 'pemilihan-ketua-rt-01-2026-2031',
        'scope_type' => 'rt',
        'description' => 'Pemilihan pimpinan RT 01 masa bakti 2026-2031.',
        'start_date' => now()->subHour(),
        'end_date' => now()->addDays(2),
        'status' => 'aktif',
        'created_by' => $this->userAdmin->id,
    ]);

    $this->kandidat1 = PemilihanKandidat::create([
        'pemilihan_id' => $this->pemilihan->id,
        'nomor_urut' => 1,
        'nama_calon' => 'Budi Santoso',
        'nama_wakil' => 'Agus Priyono',
        'slogan' => 'Maju Bersama Warga RT 01',
        'visi' => 'Lingkungan aman, bersih, dan guyub rukun.',
        'misi' => '1. Digitalisasi pelayanan warga.\n2. Ronda malam tertib.',
    ]);

    $this->kandidat2 = PemilihanKandidat::create([
        'pemilihan_id' => $this->pemilihan->id,
        'nomor_urut' => 2,
        'nama_calon' => 'Siti Rahmawati',
        'nama_wakil' => 'Dewi Lestari',
        'slogan' => 'Harmoni & Gotong Royong',
        'visi' => 'RT yang ramah keluarga dan transparan.',
        'misi' => '1. Pengelolaan kas terbuka.\n2. Posyandu aktif.',
    ]);
});

test('warga dapat melihat daftar pemilihan sesuai lingkup wilayahnya', function () {
    $this->actingAs($this->userWarga)
        ->get(route('evoting.index'))
        ->assertOk()
        ->assertSee('Pemilihan Ketua RT 01 Periode 2026-2031')
        ->assertSee('Sedang Berlangsung')
        ->assertSee('2 Pasangan Calon');
});

test('warga yang belum verifikasi e-ktp ditolak mencoblos di bilik suara', function () {
    // Tanpa RfidCard
    $this->actingAs($this->userWarga)
        ->get(route('evoting.show', $this->pemilihan->slug))
        ->assertOk()
        ->assertSee('Hak Suara Terkunci (Wajib Verifikasi e-KTP)')
        ->assertSee('Tap e-KTP untuk Membuka');

    $this->actingAs($this->userWarga)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat1->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(PemilihanSuara::count())->toBe(0);
    expect(PemilihanVoter::count())->toBe(0);
});

test('warga terverifikasi e-ktp dapat mencoblos kandidat secara rahasia', function () {
    RfidCard::create([
        'user_id' => $this->userWarga->id,
        'uid_hash' => RfidCard::hashUid('VOTE-KTP-1'),
        'ktp_verified_at' => now(),
    ]);

    $this->actingAs($this->userWarga)
        ->get(route('evoting.show', $this->pemilihan->slug))
        ->assertOk()
        ->assertSee('Bilik Suara Siap Digunakan')
        ->assertSee('Coblos Paslon 1');

    $this->actingAs($this->userWarga)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat1->id,
        ])
        ->assertRedirect(route('evoting.show', $this->pemilihan->slug))
        ->assertSessionHas('success');

    // 1 voter dicatat
    $this->assertDatabaseHas('pemilihan_voters', [
        'pemilihan_id' => $this->pemilihan->id,
        'user_id' => $this->userWarga->id,
    ]);

    // 1 suara rahasia masuk ke kandidat 1
    $this->assertDatabaseHas('pemilihan_suaras', [
        'pemilihan_id' => $this->pemilihan->id,
        'kandidat_id' => $this->kandidat1->id,
    ]);
});

test('warga tidak dapat mencoblos lebih dari satu kali (anti double voting)', function () {
    RfidCard::create([
        'user_id' => $this->userWarga->id,
        'uid_hash' => RfidCard::hashUid('VOTE-KTP-DOUBLE'),
        'ktp_verified_at' => now(),
    ]);

    // Suara pertama
    $this->actingAs($this->userWarga)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat1->id,
        ])
        ->assertSessionHas('success');

    // Suara kedua harus ditolak
    $this->actingAs($this->userWarga)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat2->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Anda sudah menggunakan hak suara Anda pada pemilihan ini.');

    expect(PemilihanSuara::count())->toBe(1);
    expect(PemilihanVoter::count())->toBe(1);
});

test('warga tidak dapat mencoblos pada pemilihan yang sudah selesai atau belum mulai', function () {
    RfidCard::create([
        'user_id' => $this->userWarga->id,
        'uid_hash' => RfidCard::hashUid('VOTE-KTP-CLOSED'),
        'ktp_verified_at' => now(),
    ]);

    $this->pemilihan->update(['status' => 'selesai']);

    $this->actingAs($this->userWarga)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat1->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(PemilihanSuara::count())->toBe(0);
});

test('warga dari rt lain tidak dapat mencoblos pemilihan rt tertentu', function () {
    $userRtLain = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rtLain->id,
        'status_akun' => 'aktif',
    ]);

    RfidCard::create([
        'user_id' => $userRtLain->id,
        'uid_hash' => RfidCard::hashUid('VOTE-KTP-LAIN'),
        'ktp_verified_at' => now(),
    ]);

    $this->actingAs($userRtLain)
        ->post(route('evoting.vote', $this->pemilihan->slug), [
            'kandidat_id' => $this->kandidat1->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Anda tidak terdaftar di wilayah RT pemilihan ini.');

    expect(PemilihanSuara::count())->toBe(0);
});

test('endpoint quick count mengembalikan data suara dan persentase yang valid', function () {
    // 2 suara ke kandidat 1, 1 suara ke kandidat 2
    PemilihanSuara::create([
        'pemilihan_id' => $this->pemilihan->id,
        'kandidat_id' => $this->kandidat1->id,
        'cast_at' => now(),
    ]);
    PemilihanSuara::create([
        'pemilihan_id' => $this->pemilihan->id,
        'kandidat_id' => $this->kandidat1->id,
        'cast_at' => now(),
    ]);
    PemilihanSuara::create([
        'pemilihan_id' => $this->pemilihan->id,
        'kandidat_id' => $this->kandidat2->id,
        'cast_at' => now(),
    ]);

    $response = $this->actingAs($this->userWarga)
        ->getJson(route('evoting.quick-count', $this->pemilihan->slug))
        ->assertOk()
        ->assertJsonStructure([
            'total_suara',
            'kandidats',
            'is_finished',
        ]);

    expect($response->json('total_suara'))->toBe(3);
    $kandidats = collect($response->json('kandidats'));
    expect($kandidats->firstWhere('id', $this->kandidat1->id)['total_suara'])->toBe(2);
    expect($kandidats->firstWhere('id', $this->kandidat1->id)['persentase'])->toBe(66.7);
    expect($kandidats->firstWhere('id', $this->kandidat1->id)['is_leading'])->toBeTrue();

    expect($kandidats->firstWhere('id', $this->kandidat2->id)['total_suara'])->toBe(1);
    expect($kandidats->firstWhere('id', $this->kandidat2->id)['persentase'])->toBe(33.3);
});

test('pengurus dapat membuat pemilihan baru dan menambahkan kandidat', function () {
    $this->actingAs($this->userAdmin)
        ->get(route('admin.evoting.create'))
        ->assertOk()
        ->assertSee('Buat Agenda Pemilihan Baru');

    $this->actingAs($this->userAdmin)
        ->post(route('admin.evoting.store'), [
            'title' => 'Pemilihan Ketua RW 05 Tahun 2026',
            'scope_type' => 'rw',
            'rw_id' => $this->rw->id,
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'description' => 'Pemilihan ketua RW.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('pemilihans', [
        'title' => 'Pemilihan Ketua RW 05 Tahun 2026',
        'scope_type' => 'rw',
    ]);

    $newPemilihan = Pemilihan::where('title', 'Pemilihan Ketua RW 05 Tahun 2026')->firstOrFail();

    $this->actingAs($this->userAdmin)
        ->post(route('admin.evoting.kandidat.store', $newPemilihan->slug), [
            'nomor_urut' => 1,
            'nama_calon' => 'Haji Lulung',
            'slogan' => 'Amanah & Merakyat',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('pemilihan_kandidats', [
        'pemilihan_id' => $newPemilihan->id,
        'nomor_urut' => 1,
        'nama_calon' => 'Haji Lulung',
    ]);
});

test('pengurus dapat memperbarui status pemilihan', function () {
    $this->actingAs($this->userAdmin)
        ->patch(route('admin.evoting.status', $this->pemilihan->slug), [
            'status' => 'selesai',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->pemilihan->fresh()->status)->toBe('selesai');
});
