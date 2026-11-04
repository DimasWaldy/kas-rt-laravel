<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('profile cannot change domicile to read another household payment proof', function (string $roleName, bool $sameRt) {
    Storage::fake('local');
    $rw = Rw::create(['name' => 'RW Test']);
    $rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $otherRt = $sameRt ? $rt : Rt::create(['name' => 'RT B', 'rw_id' => $rw->id]);
    $home = Rumah::create(['kode_rumah' => 'HOME', 'rt_id' => $rt->id]);
    $target = Rumah::create(['kode_rumah' => 'PRIVATE-HOUSE', 'rt_id' => $otherRt->id]);
    $user = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
        'rt_id' => $rt->id, 'rumah_id' => $home->id,
    ]);
    $owner = User::factory()->create(['rt_id' => $otherRt->id, 'rumah_id' => $target->id]);
    $bill = Tagihan::create([
        'user_id' => $owner->id, 'rumah_id' => $target->id, 'rt_id' => $otherRt->id,
        'bulan' => 1, 'tahun' => 2026, 'total' => 50000, 'status' => 'belum_bayar',
        'bukti' => 'tagihan-bukti/private.jpg',
    ]);
    Storage::disk('local')->put($bill->bukti, 'private proof');

    $this->actingAs($user)->get(route('tagihan.bukti', $bill))->assertForbidden();
    $this->from('/profile')->patch(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email,
        'rumah_id' => $target->id, 'rt_id' => $otherRt->id,
    ])->assertSessionHasErrors('rumah_id')->assertRedirect('/profile');
    expect($user->fresh()->rumah_id)->toBe($home->id)
        ->and($user->fresh()->rt_id)->toBe($rt->id);
    $this->get(route('tagihan.bukti', $bill))->assertForbidden();
    $this->get('/profile')->assertOk()->assertDontSee('PRIVATE-HOUSE')
        ->assertSee('Hubungi pengurus untuk menetapkan atau mengubah RT');
})->with([['warga', true], ['warga', false], ['sekretaris_rt', false]]);

test('profile cannot create a house or take responsibility for an existing house', function () {
    $user = User::factory()->create(['rumah_id' => null, 'rt_id' => null]);
    $this->actingAs($user)->from('/profile')->patch('/profile', [
        'name' => $user->name, 'email' => $user->email,
        'rumah_kode' => 'UNAUTHORIZED', 'rumah_alamat' => 'New address',
        'is_penanggung_jawab_rumah' => 1,
    ])->assertSessionHasErrors(['rumah_kode', 'rumah_alamat', 'is_penanggung_jawab_rumah']);
    expect(Rumah::where('kode_rumah', 'UNAUTHORIZED')->exists())->toBeFalse()
        ->and($user->fresh()->rumah_id)->toBeNull()
        ->and($user->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
});

test('personal profile update preserves domicile and house responsibility', function () {
    $rw = Rw::create(['name' => 'RW Test']);
    $rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $home = Rumah::create(['kode_rumah' => 'OWN', 'rt_id' => $rt->id]);
    $user = User::factory()->create([
        'rt_id' => $rt->id, 'rumah_id' => $home->id, 'is_penanggung_jawab_rumah' => true,
    ]);
    $home->update(['penanggung_jawab_id' => $user->id]);
    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated name', 'email' => $user->email, 'phone' => '081234567890',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
    expect($user->fresh()->name)->toBe('Updated name')
        ->and($user->fresh()->rumah_id)->toBe($home->id)
        ->and($user->fresh()->rt_id)->toBe($rt->id)
        ->and($user->fresh()->is_penanggung_jawab_rumah)->toBeTrue()
        ->and($home->fresh()->penanggung_jawab_id)->toBe($user->id);
});

test('onboarding resident can submit rt and domicile proposal together in personal profile update', function () {
    $rw = Rw::create(['name' => 'RW Test', 'is_active' => true]);
    $rtA = Rt::create(['name' => 'RT 01', 'rw_id' => $rw->id, 'is_active' => true]);
    $rtB = Rt::create(['name' => 'RT 02', 'rw_id' => $rw->id, 'is_active' => true]);
    $houseB = Rumah::create(['kode_rumah' => 'RT2-01', 'alamat' => 'Jalan RT Dua No. 1', 'rt_id' => $rtB->id, 'status' => 'aktif']);

    $user = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id') ?? Role::firstOrCreate(['name' => 'warga'])->id,
        'rt_id' => $rtA->id,
        'rumah_id' => null,
        'onboarding_domisili' => true,
        'status_akun' => 'aktif',
    ]);
    $warga = \App\Models\Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'status_verifikasi' => 'terverifikasi',
        'status_domisili' => 'belum_mengajukan',
    ]);

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Warga Pindah RT',
        'email' => $user->email,
        'phone' => '081234567890',
        'rt_id' => $rtB->id,
        'mode' => 'existing',
        'rumah_id' => $houseB->id,
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect($user->fresh()->rt_id)->toBe($rtB->id)
        ->and($user->fresh()->rumah_id)->toBeNull()
        ->and($warga->fresh()->status_domisili)->toBe('pending')
        ->and($warga->fresh()->rumah_diajukan_id)->toBe($houseB->id);
});

test('warga sees updated family card after onboarding profile submission and officer verification', function () {
    $rw = Rw::create(['name' => 'RW KK Test', 'is_active' => true]);
    $rt = Rt::create(['name' => 'RT KK Test', 'rw_id' => $rw->id, 'is_active' => true]);
    $house = Rumah::create(['kode_rumah' => 'KK-01', 'alamat' => 'Jalan KK No 1', 'rt_id' => $rt->id, 'status' => 'aktif']);

    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $officer = User::factory()->create([
        'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
        'rt_id' => $rt->id,
        'status_akun' => 'aktif',
    ]);

    $wargaUser = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id') ?? Role::firstOrCreate(['name' => 'warga'])->id,
        'rt_id' => $rt->id,
        'rumah_id' => null,
        'onboarding_domisili' => true,
        'status_akun' => 'aktif',
    ]);
    $warga = \App\Models\Warga::create([
        'user_id' => $wargaUser->id,
        'nama_lengkap' => $wargaUser->name,
        'status_verifikasi' => 'pending',
        'status_domisili' => 'belum_mengajukan',
    ]);

    $this->actingAs($wargaUser)->patch('/profile', [
        'name' => 'Warga Berkeluarga',
        'email' => $wargaUser->email,
        'phone' => '081234567890',
        'nik' => '3201010101010001',
        'no_kk' => '3201010101010002',
        'status_dalam_kk' => 'kepala_keluarga',
        'rt_id' => $rt->id,
        'mode' => 'existing',
        'rumah_id' => $house->id,
    ])->assertSessionHasNoErrors();

    expect($warga->fresh()->kartu_keluarga_id)->not->toBeNull()
        ->and($warga->fresh()->kartuKeluarga->no_kk)->toBe('3201010101010002')
        ->and($warga->fresh()->kartuKeluarga->nama_kepala_keluarga)->toBe('Warga Berkeluarga')
        ->and($warga->fresh()->status_dalam_kk)->toBe('kepala_keluarga');

    $this->actingAs($officer)->patch(route('verifikasi-warga.verifikasi', $warga), [
        'metode_verifikasi' => 'tatap_muka',
    ])->assertSessionHasNoErrors();

    expect($warga->fresh()->status_verifikasi)->toBe('terverifikasi')
        ->and($warga->fresh()->kartuKeluarga->rumah_id)->toBe($house->id)
        ->and($wargaUser->fresh()->rumah_id)->toBe($house->id);

    $this->actingAs($wargaUser->fresh())
        ->get('/profile')
        ->assertOk()
        ->assertSee('3201010101010002')
        ->assertSee('Warga Berkeluarga')
        ->assertSee('Kepala Keluarga');
});

test('profile edit returns json list of houses when requested with json header', function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW 01', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $house = Rumah::create([
        'rt_id' => $rt->id,
        'kode_rumah' => 'A1',
        'alamat' => 'Jl. Mawar No. 1',
        'status' => 'aktif',
    ]);

    $user = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->firstOrFail()->id,
        'rt_id' => $rt->id,
        'rumah_id' => null,
        'onboarding_domisili' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/profile?rt_id=' . $rt->id);

    $response->assertOk()
        ->assertJsonStructure(['rumahs' => [['id', 'rt_id', 'kode_rumah', 'alamat']]])
        ->assertJsonFragment(['id' => $house->id, 'kode_rumah' => $house->kode_rumah]);
});

test('warga can save profile with no_kk as member without null constraint error', function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW 01', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 09', 'is_active' => true]);

    $roleWarga = Role::where('name', 'warga')->firstOrFail();
    $user = User::factory()->create([
        'role_id' => $roleWarga->id,
        'status_akun' => 'aktif',
        'rt_id' => $rt->id,
        'onboarding_domisili' => true,
    ]);

    $warga = \App\Models\Warga::create([
        'user_id' => $user->id,
        'rt_id' => $rt->id,
        'nama_lengkap' => 'Warga Anggota',
        'rumah_id' => null,
        'kartu_keluarga_id' => null,
        'status_verifikasi' => 'pending',
        'status_domisili' => 'belum_mengajukan',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Warga Anggota',
        'email' => $user->email,
        'phone' => '081299998888',
        'rt_id' => $rt->id,
        'nik' => '3201123456789099',
        'no_kk' => '3201123456789088',
        'status_dalam_kk' => 'anggota',
        'opsi_verifikasi' => 'tatap_muka',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kartu_keluargas', [
        'no_kk' => '3201123456789088',
    ]);

    $kk = \App\Models\KartuKeluarga::where('no_kk', '3201123456789088')->first();
    expect($kk->nama_kepala_keluarga)->not->toBeNull();
});

test('warga can save optional family count and home occupant count on onboarding profile', function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW 01', 'is_active' => true]);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 07', 'is_active' => true]);

    $roleWarga = Role::where('name', 'warga')->firstOrFail();
    $user = User::factory()->create([
        'role_id' => $roleWarga->id,
        'status_akun' => 'aktif',
        'rt_id' => $rt->id,
        'onboarding_domisili' => true,
    ]);

    $warga = \App\Models\Warga::create([
        'user_id' => $user->id,
        'rt_id' => $rt->id,
        'nama_lengkap' => 'Kepala Keluarga Baru',
        'rumah_id' => null,
        'kartu_keluarga_id' => null,
        'status_verifikasi' => 'pending',
        'status_domisili' => 'belum_mengajukan',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Kepala Keluarga Baru',
        'email' => $user->email,
        'phone' => '081277778888',
        'rt_id' => $rt->id,
        'nik' => '3201123456780001',
        'no_kk' => '3201123456780002',
        'status_dalam_kk' => 'kepala_keluarga',
        'jumlah_anggota' => 4,
        'jumlah_penghuni_rumah' => 6,
        'opsi_verifikasi' => 'tatap_muka',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('kartu_keluargas', [
        'no_kk' => '3201123456780002',
        'jumlah_anggota' => 4,
        'nama_kepala_keluarga' => 'Kepala Keluarga Baru',
    ]);
    $this->assertDatabaseHas('wargas', [
        'id' => $warga->id,
        'jumlah_penghuni_rumah' => 6,
    ]);
});
