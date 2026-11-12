<?php

use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\KoperasiMember;
use App\Models\RfidCard;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->rw = Rw::create(['name' => 'RW KTP RFID', 'is_active' => true]);
    $this->rt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->otherRt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 02', 'is_active' => true]);
    $this->rumah = Rumah::create(['kode_rumah' => 'KTP-01', 'rt_id' => $this->rt->id, 'status' => 'aktif']);

    $makeUser = fn (string $role = 'warga', ?Rt $rt = null, array $attrs = []) => User::factory()->create([
        'role_id' => Role::where('name', $role)->value('id'),
        'rt_id' => ($rt ?? $this->rt)->id,
        'status_akun' => 'aktif',
        ...$attrs,
    ]);

    $this->ketuaRt = $makeUser('ketua_rt');
    $this->otherKetuaRt = $makeUser('ketua_rt', $this->otherRt);
    $this->superAdmin = User::factory()->create([
        'role_id' => Role::where('name', 'super_admin')->value('id'),
        'status_akun' => 'aktif',
    ]);

    $this->wargaUser = $makeUser('warga', $this->rt, [
        'rumah_id' => $this->rumah->id,
        'onboarding_domisili' => false,
    ]);
    Warga::create([
        'user_id' => $this->wargaUser->id,
        'nama_lengkap' => $this->wargaUser->name,
        'status_dalam_kk' => 'kepala_keluarga',
        'status_verifikasi' => 'terverifikasi',
    ]);

    $this->pendingWargaUser = $makeUser('warga', $this->rt, [
        'status_akun' => 'pending_verifikasi',
    ]);
    Warga::create([
        'user_id' => $this->pendingWargaUser->id,
        'nama_lengkap' => $this->pendingWargaUser->name,
        'status_dalam_kk' => 'anggota',
        'status_verifikasi' => 'pending',
    ]);

    $this->otherWargaUser = $makeUser('warga', $this->otherRt);

    $this->openWargaRfid = function (?User $actor = null) {
        $this->actingAs($actor ?? $this->ketuaRt)
            ->post(route('admin.warga.rfid.open'))
            ->assertRedirect()
            ->assertSessionHas('rfid_token');

        return session('rfid_token');
    };

    $this->tap = fn (string $token, string $uid = 'A1B2C3D4') => $this->postJson(
        route('rfid.tap'),
        ['uid' => $uid],
        ['Authorization' => 'Bearer '.$token]
    );
});

test('warga yang akunnya belum diverifikasi tidak bisa verifikasi ktp rfid, sedangkan warga terverifikasi langsung terdaftar di rt dan sistem', function () {
    // Layanan dasar warga tetap terbuka sebelum KTP tap, namun tampil notice belum verifikasi KTP.
    $this->actingAs($this->wargaUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('KTP belum diverifikasi via RFID');

    $this->actingAs($this->ketuaRt)
        ->get(route('verifikasi-warga.index'))
        ->assertOk()
        ->assertSee('Verifikasi KTP (RFID)');

    $this->actingAs($this->ketuaRt)
        ->get(route('admin.warga.index'))
        ->assertOk()
        ->assertSee('Verifikasi KTP (RFID)')
        ->assertSee('Belum Tap KTP');

    // Warga yang akunnya masih pending verifikasi tidak boleh ikut verifikasi KTP RFID.
    $token = ($this->openWargaRfid)();

    $this->actingAs($this->ketuaRt)
        ->get(route('admin.warga.rfid.show'))
        ->assertOk()
        ->assertSee('Token alat verifikasi KTP')
        ->assertSee($token)
        ->assertSee($this->wargaUser->name)
        ->assertDontSee($this->pendingWargaUser->name)
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($this->ketuaRt)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $this->pendingWargaUser->id])
        ->assertNotFound();

    $this->actingAs($this->ketuaRt)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $this->wargaUser->id])
        ->assertSessionHasNoErrors();

    ($this->tap)($token)
        ->assertOk()
        ->assertJsonPath('status', 'ktp_registered');

    $card = RfidCard::where('user_id', $this->wargaUser->id)->firstOrFail();
    expect($card->uid_hash)->toBe(RfidCard::hashUid('A1B2C3D4'))
        ->and($card->ktp_verified_at)->not->toBeNull()
        ->and($card->verified_by)->toBe($this->ketuaRt->id)
        ->and($card->verified_rt_id)->toBe($this->rt->id)
        ->and($card->koperasi_verified_at)->not->toBeNull();

    $this->actingAs($this->ketuaRt)
        ->getJson(route('admin.warga.rfid.status'))
        ->assertOk()
        ->assertJsonPath('open', true)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('attendees.0.name', $this->wargaUser->name.' (RT 01)');

    $this->actingAs($this->ketuaRt)
        ->get(route('admin.warga.index'))
        ->assertOk()
        ->assertSee('Terdaftar (RT 01)');

    $this->actingAs($this->wargaUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('KTP Terdaftar di RT 01');
});

test('1x verifikasi ktp rfid di rt atau superadmin langsung berlaku untuk daftar koperasi dan absensi kegiatan', function () {
    $token = ($this->openWargaRfid)($this->superAdmin);
    $this->actingAs($this->superAdmin)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $this->wargaUser->id])
        ->assertSessionHasNoErrors();
    ($this->tap)($token, '1234ABCD')->assertOk()->assertJsonPath('status', 'ktp_registered');

    // Langsung bisa buka & daftar koperasi tanpa tap ulang.
    $this->actingAs($this->wargaUser)
        ->get(route('koperasi.index'))
        ->assertOk()
        ->assertSee('Daftar Sekarang');

    $this->actingAs($this->wargaUser)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    expect(KoperasiMember::where('user_id', $this->wargaUser->id)->exists())->toBeTrue();

    // Langsung bisa tap absensi kegiatan tanpa daftar kartu ulang.
    $kegiatan = Kegiatan::create([
        'rw_id' => $this->rw->id,
        'rt_id' => $this->rt->id,
        'created_by' => $this->ketuaRt->id,
        'nama' => 'Kerja Bakti RFID',
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHours(2),
        'status' => 'berlangsung',
    ]);
    $this->actingAs($this->ketuaRt)
        ->post(route('kegiatan.rfid.open', $kegiatan))
        ->assertRedirect()
        ->assertSessionHas('rfid_token');
    $kegiatanToken = session('rfid_token');

    ($this->tap)($kegiatanToken, '1234abcd')
        ->assertOk()
        ->assertJsonPath('status', 'present');

    expect(KegiatanHadir::where('kegiatan_id', $kegiatan->id)->where('user_id', $this->wargaUser->id)->whereNotNull('rfid_hadir_at')->exists())->toBeTrue();
});

test('kartu yang sudah ada dari kegiatan dapat langsung diverifikasi sebagai ktp di sesi rt', function () {
    $card = RfidCard::create([
        'user_id' => $this->wargaUser->id,
        'uid_hash' => RfidCard::hashUid('AABBCCDD'),
    ]);

    $token = ($this->openWargaRfid)();

    ($this->tap)($token, 'aabbccdd')
        ->assertOk()
        ->assertJsonPath('status', 'ktp_verified');

    expect($card->fresh()->ktp_verified_at)->not->toBeNull()
        ->and($card->fresh()->verified_rt_id)->toBe($this->rt->id)
        ->and($card->fresh()->koperasi_verified_at)->not->toBeNull();

    ($this->tap)($token, 'AABBCCDD')
        ->assertOk()
        ->assertJsonPath('status', 'ktp_already_verified');
});

test('verifikasi ktp rfid membatasi izin wilayah, masa berlaku token, dan pencabutan ktp', function () {
    $this->actingAs($this->wargaUser)->get(route('admin.warga.rfid.show'))->assertForbidden();
    $this->actingAs($this->wargaUser)->post(route('admin.warga.rfid.open'))->assertForbidden();

    $token = ($this->openWargaRfid)();

    $this->actingAs($this->otherKetuaRt)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $this->wargaUser->id])
        ->assertNotFound();

    RfidCard::create([
        'user_id' => $this->otherWargaUser->id,
        'uid_hash' => RfidCard::hashUid('99887766'),
    ]);
    ($this->tap)($token, '99887766')->assertNotFound();

    ($this->tap)($token, 'FEEDBEEF')->assertUnprocessable();

    // Enroll lalu cabut kartu.
    $this->actingAs($this->ketuaRt)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $this->wargaUser->id])
        ->assertSessionHasNoErrors();
    ($this->tap)($token, '00112233')->assertOk();

    $this->actingAs($this->ketuaRt)
        ->post(route('admin.warga.rfid.revoke'), ['user_id' => $this->wargaUser->id])
        ->assertRedirect();
    expect(RfidCard::where('user_id', $this->wargaUser->id)->exists())->toBeFalse();

    // Tutup sesi & kedaluwarsa memblokir tap.
    $this->actingAs($this->ketuaRt)->post(route('admin.warga.rfid.close'))->assertRedirect();
    ($this->tap)($token, '00112233')->assertUnauthorized();

    $freshToken = ($this->openWargaRfid)();
    $this->travel(6)->hours();
    ($this->tap)($freshToken, '00112233')->assertUnauthorized();
});
