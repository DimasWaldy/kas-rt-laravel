<?php

use App\Models\GerbangLog;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create(['name' => 'RW Gerbang', 'is_active' => true]);
    $this->otherRw = Rw::create(['name' => 'RW Tetangga', 'is_active' => true]);
    $this->rt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->otherRt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT 02', 'is_active' => true]);

    $makeUser = fn (string $role, array $attrs = []) => User::factory()->create([
        'role_id' => Role::where('name', $role)->value('id'),
        'status_akun' => 'aktif',
        ...$attrs,
    ]);

    $this->ketuaRt = $makeUser('ketua_rt', ['rt_id' => $this->rt->id]);
    $this->otherKetuaRt = $makeUser('ketua_rt', ['rt_id' => $this->otherRt->id]);
    $this->ketuaRw = $makeUser('ketua_rw', ['rw_id' => $this->rw->id, 'rt_id' => null]);
    $this->superAdmin = $makeUser('super_admin', ['rt_id' => null, 'rw_id' => null]);

    $this->warga = $makeUser('warga', ['rt_id' => $this->rt->id]);
    $this->otherRtWarga = $makeUser('warga', ['rt_id' => $this->otherRt->id]);
    $this->unverifiedWarga = $makeUser('warga', ['rt_id' => $this->rt->id]);

    $this->verifiedCard = RfidCard::create([
        'user_id' => $this->warga->id,
        'uid_hash' => RfidCard::hashUid('A1B2C3D4'),
        'ktp_verified_at' => now(),
        'verified_by' => $this->ketuaRt->id,
        'verified_rt_id' => $this->rt->id,
    ]);

    $this->otherRtCard = RfidCard::create([
        'user_id' => $this->otherRtWarga->id,
        'uid_hash' => RfidCard::hashUid('BBCCDDEE'),
        'ktp_verified_at' => now(),
        'verified_by' => $this->otherKetuaRt->id,
        'verified_rt_id' => $this->otherRt->id,
    ]);

    $this->unverifiedCard = RfidCard::create([
        'user_id' => $this->unverifiedWarga->id,
        'uid_hash' => RfidCard::hashUid('11223344'),
        'ktp_verified_at' => null,
    ]);
});

test('warga biasa tidak dapat mengakses panel kontrol gerbang rfid', function () {
    $this->actingAs($this->warga)->get(route('keamanan.gerbang.show'))->assertForbidden();
    $this->actingAs($this->warga)->post(route('keamanan.gerbang.open'))->assertForbidden();
    $this->actingAs($this->warga)->post(route('keamanan.gerbang.mode'), ['gate_mode' => 'masuk'])->assertForbidden();
    $this->actingAs($this->warga)->post(route('keamanan.gerbang.close'))->assertForbidden();
    $this->actingAs($this->warga)->getJson(route('keamanan.gerbang.status'))->assertForbidden();
});

test('ktp terverifikasi dapat tap gerbang masuk keluar otomatis dengan sinyal servo dan buzzer', function () {
    $this->actingAs($this->ketuaRt)
        ->get(route('keamanan.gerbang.show'))
        ->assertOk()
        ->assertSee('Kontrol Gerbang Masuk')
        ->assertSee('liveScan');

    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.open'), ['gate_mode' => 'auto'])
        ->assertRedirect(route('keamanan.gerbang.show'))
        ->assertSessionHas('rfid_token');

    $tapMasuk = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'a1b2c3d4', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    );

    $tapMasuk->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'gate_opened',
            'open_gate' => true,
            'servo' => true,
            'direction' => 'masuk',
            'buzzer' => 'gate_in',
            'user_name' => $this->warga->name,
        ]);

    $tapKeluar = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    );

    $tapKeluar->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'gate_opened',
            'open_gate' => true,
            'servo' => true,
            'direction' => 'keluar',
            'buzzer' => 'gate_out',
        ]);

    $tapMasukLagi = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    );

    $tapMasukLagi->assertOk()->assertJsonPath('direction', 'masuk');

    expect(GerbangLog::where('user_id', $this->warga->id)->count())->toBe(3);

    $status = $this->actingAs($this->ketuaRt)
        ->getJson(route('keamanan.gerbang.status'))
        ->assertOk()
        ->assertJsonPath('open', true)
        ->assertJsonPath('gate_mode', 'auto')
        ->assertJsonPath('stats.masuk_today', 2)
        ->assertJsonPath('stats.keluar_today', 1)
        ->assertJsonPath('stats.ditolak_today', 0)
        ->assertJsonPath('last_scan.direction', 'masuk')
        ->assertJsonPath('last_scan.open_gate', true)
        ->assertJsonPath('last_scan.uid_masked', 'A1••••D4')
        ->assertDontSee('A1B2C3D4');

    expect($status->json('logs'))->toHaveCount(3);
});

test('mode khusus masuk dan keluar selalu memaksa arah sesuai pilihan operator', function () {
    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.mode'), ['gate_mode' => 'masuk'])
        ->assertRedirect();

    $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'], ['X-Device-Key' => 'smartrw-rfid-device'])
        ->assertOk()
        ->assertJsonPath('direction', 'masuk')
        ->assertJsonPath('buzzer', 'gate_in');

    $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'], ['X-Device-Key' => 'smartrw-rfid-device'])
        ->assertOk()
        ->assertJsonPath('direction', 'masuk');

    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.mode'), ['gate_mode' => 'keluar'])
        ->assertRedirect();

    $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'], ['X-Device-Key' => 'smartrw-rfid-device'])
        ->assertOk()
        ->assertJsonPath('direction', 'keluar')
        ->assertJsonPath('buzzer', 'gate_out');
});

test('kartu tidak dikenal, ktp belum terverifikasi, dan warga luar rt ditolak serta tetap tercatat di log gerbang', function () {
    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.open'), ['gate_mode' => 'auto'])
        ->assertRedirect();

    // 1. Kartu tidak terdaftar sama sekali
    $this->postJson(
        route('rfid.tap'),
        ['uid' => 'DEADBEEF', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )->assertUnprocessable();

    // 2. Kartu ada namun ktp_verified_at null
    $this->postJson(
        route('rfid.tap'),
        ['uid' => '11223344', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )->assertUnprocessable();

    // 3. Warga RT 02 tap di gerbang milik RT 01
    $this->postJson(
        route('rfid.tap'),
        ['uid' => 'BBCCDDEE', 'device_id' => 'esp32-gerbang-01'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )->assertForbidden();

    $rejectedLogs = GerbangLog::where('rt_id', $this->rt->id)
        ->where('status', 'ditolak')
        ->get();

    expect($rejectedLogs)->toHaveCount(3)
        ->and($rejectedLogs->pluck('uid_masked')->all())->toContain('DE••••EF', '11••••44', 'BB••••EE');

    $this->actingAs($this->ketuaRt)
        ->getJson(route('keamanan.gerbang.status'))
        ->assertOk()
        ->assertJsonPath('stats.ditolak_today', 3)
        ->assertDontSee('DEADBEEF');

    // Ketua RT 02 tidak melihat log penolakan gerbang milik RT 01
    $this->actingAs($this->otherKetuaRt)
        ->getJson(route('keamanan.gerbang.status'))
        ->assertOk()
        ->assertJsonPath('stats.ditolak_today', 0)
        ->assertJsonCount(0, 'logs');
});

test('gerbang tingkat rw mengizinkan warga lintas rt dalam rw yang sama dan riwayat tampil di halaman warga', function () {
    $this->actingAs($this->ketuaRw)
        ->post(route('keamanan.gerbang.open'), ['gate_mode' => 'auto'])
        ->assertRedirect();

    $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )->assertOk()->assertJsonPath('direction', 'masuk');

    $this->postJson(
        route('rfid.tap'),
        ['uid' => 'BBCCDDEE'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )->assertOk()->assertJsonPath('direction', 'masuk');

    $this->actingAs($this->warga)
        ->get(route('warga.rfid.show'))
        ->assertOk()
        ->assertSee('Riwayat Akses Gerbang Terbaru')
        ->assertSee('MASUK');
});

test('endpoint gate tap dan mode gerbang langsung membuka gerbang tanpa mengganggu enrollment ktp', function () {
    // 1. Jika belum ada sesi apapun, standby default device key langsung memakai sesi master gerbang
    $this->postJson(
        route('rfid.gate.tap'),
        ['uid' => 'A1B2C3D4', 'device_id' => 'esp32-gate'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )
        ->assertOk()
        ->assertJsonPath('status', 'gate_opened')
        ->assertJsonPath('open_gate', true)
        ->assertJsonPath('direction', 'masuk');

    // 2. Meski sesi warga dibuka, route /api/rfid/gate/tap atau payload mode=gerbang tetap melayani gerbang
    $this->actingAs($this->ketuaRt)->post(route('admin.warga.rfid.open'))->assertRedirect();

    $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4', 'mode' => 'gerbang'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )
        ->assertOk()
        ->assertJsonPath('status', 'gate_opened')
        ->assertJsonPath('direction', 'keluar');

    // 3. Jika sedang enrollment aktif (Aktifkan Tap KTP), enrollment tetap diprioritaskan (servo tidak membuka gerbang)
    $newWarga = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id'),
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);
    $this->actingAs($this->ketuaRt)
        ->post(route('admin.warga.rfid.enroll'), ['user_id' => $newWarga->id])
        ->assertRedirect();

    $this->postJson(
        route('rfid.tap'),
        ['uid' => '99887766', 'mode' => 'gerbang'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    )
        ->assertOk()
        ->assertJsonPath('status', 'ktp_registered')
        ->assertJsonPath('open_gate', false);

    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.open'))
        ->assertRedirect();

    $this->actingAs($this->ketuaRt)
        ->post(route('keamanan.gerbang.close'))
        ->assertRedirect();

    expect(RfidSession::where('scope_key', "gerbang:rt:{$this->rt->id}")->value('expires_at')?->isPast())->toBeTrue();
});
