<?php

use App\Models\KoperasiMember;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->rwKoperasi = Rw::create(['name' => 'RW Koperasi RFID', 'is_active' => true]);
    $this->rtKoperasi = Rt::create(['rw_id' => $this->rwKoperasi->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->otherRtKoperasi = Rt::create(['rw_id' => $this->rwKoperasi->id, 'name' => 'RT 02', 'is_active' => true]);

    $makeUser = fn (string $role = 'warga', ?Rt $rt = null) => User::factory()->create([
        'role_id' => Role::where('name', $role)->value('id'),
        'rt_id' => ($rt ?? $this->rtKoperasi)->id,
        'status_akun' => 'aktif',
    ]);

    $this->bendahara = $makeUser('bendahara');
    $this->otherBendahara = $makeUser('bendahara', $this->otherRtKoperasi);
    $this->warga = $makeUser('warga');
    $this->otherWarga = $makeUser('warga', $this->otherRtKoperasi);

    $this->openKoperasiRfid = function () {
        $this->actingAs($this->bendahara)
            ->post(route('admin.koperasi.rfid.open'))
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

test('daftar koperasi wajib tap kartu baru lewat sesi rfid koperasi', function () {
    $this->actingAs($this->warga)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('error');
    expect(KoperasiMember::count())->toBe(0);

    $token = ($this->openKoperasiRfid)();
    $session = RfidSession::where('scope_type', 'koperasi')->firstOrFail();
    expect($session->token_hash)->toBe(hash('sha256', $token))->not->toBe($token);

    $this->actingAs($this->bendahara)
        ->get(route('admin.koperasi.rfid.show'))
        ->assertOk()
        ->assertSee('Token alat koperasi')
        ->assertSee($token)
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($this->bendahara)
        ->post(route('admin.koperasi.rfid.enroll'), ['user_id' => $this->warga->id])
        ->assertSessionHasNoErrors();

    ($this->tap)($token)
        ->assertOk()
        ->assertJsonPath('status', 'koperasi_registered');

    $card = RfidCard::where('user_id', $this->warga->id)->firstOrFail();
    expect($card->uid_hash)->toBe(RfidCard::hashUid('A1B2C3D4'))
        ->and($card->koperasi_verified_at)->not->toBeNull()
        ->and($session->fresh()->enrollment_user_id)->toBeNull();

    $this->actingAs($this->bendahara)
        ->getJson(route('admin.koperasi.rfid.status'))
        ->assertOk()
        ->assertJsonPath('open', true)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('attendees.0.name', $this->warga->name);

    $this->actingAs($this->warga)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_members', [
        'user_id' => $this->warga->id,
        'status' => 'pending',
    ]);
});

test('warga yang sudah punya kartu dari kegiatan tetap wajib tap di sesi koperasi sebelum daftar', function () {
    $card = RfidCard::create([
        'user_id' => $this->warga->id,
        'uid_hash' => RfidCard::hashUid('CAFEBABE'),
    ]);

    $this->actingAs($this->warga)
        ->post(route('koperasi.store-daftar'))
        ->assertSessionHas('error');
    expect(KoperasiMember::count())->toBe(0);

    $token = ($this->openKoperasiRfid)();

    ($this->tap)($token, 'cafebabe')
        ->assertOk()
        ->assertJsonPath('status', 'koperasi_verified');
    expect($card->fresh()->koperasi_verified_at)->not->toBeNull();

    ($this->tap)($token, 'CAFEBABE')
        ->assertOk()
        ->assertJsonPath('status', 'koperasi_already_verified');

    $this->actingAs($this->warga)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    expect(KoperasiMember::where('user_id', $this->warga->id)->exists())->toBeTrue();
});

test('sesi rfid koperasi membatasi izin wilayah dan masa berlaku token', function () {
    $this->actingAs($this->warga)->get(route('admin.koperasi.rfid.show'))->assertForbidden();
    $this->actingAs($this->warga)->post(route('admin.koperasi.rfid.open'))->assertForbidden();

    $token = ($this->openKoperasiRfid)();

    // Bendahara RT lain tidak dapat mendaftarkan warga RT ini atau mencabut kartunya.
    $this->actingAs($this->otherBendahara)
        ->post(route('admin.koperasi.rfid.enroll'), ['user_id' => $this->warga->id])
        ->assertNotFound();

    // Tap kartu milik warga RT lain pada sesi RT ini ditolak.
    RfidCard::create([
        'user_id' => $this->otherWarga->id,
        'uid_hash' => RfidCard::hashUid('11223344'),
    ]);
    ($this->tap)($token, '11223344')->assertNotFound();

    // Kartu belum dikenal ditolak 422.
    ($this->tap)($token, 'DEADBEEF')->assertUnprocessable();

    // Tutup sesi & kedaluwarsa memblokir tap.
    $this->actingAs($this->bendahara)->post(route('admin.koperasi.rfid.close'))->assertRedirect();
    ($this->tap)($token, '11223344')->assertUnauthorized();

    $freshToken = ($this->openKoperasiRfid)();
    $this->travel(6)->hours();
    ($this->tap)($freshToken, '11223344')->assertUnauthorized();
});

test('pencabutan kartu menggagalkan pendaftaran koperasi sebelum submit', function () {
    $token = ($this->openKoperasiRfid)();
    $this->actingAs($this->bendahara)
        ->post(route('admin.koperasi.rfid.enroll'), ['user_id' => $this->warga->id])
        ->assertSessionHasNoErrors();
    ($this->tap)($token)->assertOk();

    $this->actingAs($this->bendahara)
        ->post(route('admin.koperasi.rfid.revoke'), ['user_id' => $this->warga->id])
        ->assertRedirect();

    $this->actingAs($this->warga)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('error');

    expect(KoperasiMember::count())->toBe(0);
});
