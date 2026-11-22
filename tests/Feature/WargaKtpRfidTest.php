<?php

use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\KoperasiMember;
use App\Models\RfidCard;
use App\Models\RfidSession;
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

test('warga dapat melihat kartu digital dan panduan verifikasi rfid di halaman khusus serta shortcut dashboard dan sidebar', function () {
    $this->get(route('warga.rfid.show'))->assertRedirect(route('login'));

    $this->actingAs($this->wargaUser)
        ->get(route('warga.rfid.show'))
        ->assertOk()
        ->assertSee('Kartu KTP &amp; Verifikasi RFID', false)
        ->assertSee('Cara Melakukan Verifikasi KTP via RFID')
        ->assertSee('Tanya Jawab (FAQ) Verifikasi e-KTP')
        ->assertSee('Belum Di-Tap')
        ->assertSee('Sesi Alat Belum Dibuka');

    $this->actingAs($this->wargaUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('warga.rfid.show'))
        ->assertSee('Lihat Cara Tap')
        ->assertSee('Panduan Tap')
        ->assertSee('Panduan Verifikasi e-KTP');

    $this->actingAs($this->ketuaRt)
        ->get(route('admin.warga.rfid.show'))
        ->assertOk()
        ->assertSee('Panduan Petugas &amp; Setup Reader RFID', false)
        ->assertSee('Setup Alat Reader ESP32');

    // Ketika KTP sudah diverifikasi
    RfidCard::create([
        'user_id' => $this->wargaUser->id,
        'uid_hash' => RfidCard::hashUid('11223344'),
        'ktp_verified_at' => now(),
        'verified_by_rt_id' => $this->rt->id,
    ]);

    $this->actingAs($this->wargaUser)
        ->get(route('warga.rfid.show'))
        ->assertOk()
        ->assertSee('Terdaftar Resmi');

    $this->actingAs($this->wargaUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kartu Digital');
});

test('warga dapat membuka sesi tap e-ktp mandiri dan melakukan verifikasi langsung dari alat reader esp32', function () {
    // 1. Warga belum diverifikasi membuka sesi mandiri
    $response = $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'remaining_seconds' => 180,
        ]);

    // 2. Status mencerminkan warga sedang mendaftar (enrolling)
    $this->actingAs($this->wargaUser)
        ->getJson(route('warga.rfid.status'))
        ->assertOk()
        ->assertJson([
            'is_enrolling' => true,
            'is_verified' => false,
        ]);

    // 3. Reader ESP32 yang terhubung ke RT dapat melakukan tap e-KTP dan sukses mendaftarkan KTP warga
    $scopeKey = 'warga:rt:'.$this->wargaUser->rt_id;
    $session = RfidSession::where('scope_key', $scopeKey)->first();
    expect($session)->not->toBeNull();
    expect($session->enrollment_user_id)->toBe($this->wargaUser->id);

    // Dapatkan token bearer dari sesi (simulasi alat ESP32)
    // Untuk tes, kita set token 64-hex yang diketahui
    $deviceToken = str_repeat('a', 64);
    $session->update(['token_hash' => hash('sha256', $deviceToken)]);

    $tapResponse = ($this->tap)($deviceToken, 'A1B2C3D4');
    $tapResponse->assertOk()->assertJson([
        'status' => 'ktp_registered',
        'message' => 'KTP warga berhasil didaftarkan dan tercatat di RT serta sistem.',
    ]);

    // 4. KTP warga langsung terverifikasi resmi di database
    $card = RfidCard::where('user_id', $this->wargaUser->id)->first();
    expect($card)->not->toBeNull();
    expect($card->ktp_verified_at)->not->toBeNull();
    expect($card->uid_hash)->toBe(RfidCard::hashUid('A1B2C3D4'));

    // 5. Status endpoint sekarang mengembalikan is_verified = true
    $this->actingAs($this->wargaUser)
        ->getJson(route('warga.rfid.status'))
        ->assertOk()
        ->assertJson([
            'is_enrolling' => false,
            'is_verified' => true,
        ]);

    // 6. Warga yang sudah terverifikasi tidak bisa membuka sesi lagi
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertStatus(422);

    // 7. Warga dengan akun belum diverifikasi tidak bisa buka sesi mandiri (403 Forbidden dari active middleware)
    $this->actingAs($this->pendingWargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertStatus(403);
});

test('warga dapat membatalkan sesi tap mandiri', function () {
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertOk();

    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.cancel'))
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->actingAs($this->wargaUser)
        ->getJson(route('warga.rfid.status'))
        ->assertOk()
        ->assertJson([
            'is_enrolling' => false,
        ]);
});

test('warga dapat memverifikasi e-ktp langsung menggunakan sensor nfc smartphone', function () {
    // 1. Warga terverifikasi mengirim UID e-KTP dari sensor NFC HP (misal 14-char ISO 14443 UID)
    $response = $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), [
            'uid' => '04:5A:32:12:8B:50:80',
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    // 2. Data kartu KTP tersimpan dan terverifikasi di database
    $card = RfidCard::where('user_id', $this->wargaUser->id)->first();
    expect($card)->not->toBeNull();
    expect($card->ktp_verified_at)->not->toBeNull();
    expect($card->uid_hash)->toBe(RfidCard::hashUid('045A32128B5080'));

    // 3. Warga lain tidak boleh mendaftarkan UID KTP yang sama
    $responseDup = $this->actingAs($this->otherWargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), [
            'uid' => '045A32128B5080',
        ])
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    // 4. Warga yang sudah terverifikasi tidak bisa tap ulang
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), [
            'uid' => '11223344',
        ])
        ->assertStatus(422);

    // 5. Format UID yang tidak valid (kurang atau bukan hex) ditolak
    $this->actingAs($this->otherWargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), [
            'uid' => 'INVALID-UID',
        ])
        ->assertStatus(422);

    // 6. Warga pending tidak boleh tap NFC
    $this->actingAs($this->pendingWargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), [
            'uid' => 'A1B2C3D4',
        ])
        ->assertStatus(403);
});

test('rfid tap mendukung device key permanen tanpa memerlukan session token manual', function () {
    // 1. Warga aktifkan sesi pendaftaran e-KTP
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertOk();

    // 2. Reader Arduino/ESP32 tap dengan kunci permanen "smartrw-rfid-device"
    $response = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4'],
        ['Authorization' => 'Bearer smartrw-rfid-device']
    );

    $response->assertOk()
        ->assertJsonPath('status', 'ktp_registered');

    // 3. Verifikasi e-KTP berhasil tercatat di database
    $card = RfidCard::where('user_id', $this->wargaUser->id)->first();
    expect($card)->not->toBeNull()
        ->and($card->ktp_verified_at)->not->toBeNull();

    // 4. Tap kedua kali dengan e-KTP yang sudah terdaftar
    $responseSecond = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4'],
        ['Authorization' => 'Bearer smartrw-rfid-device']
    );

    $responseSecond->assertOk()
        ->assertJsonPath('status', 'ktp_already_verified');
});

test('pendaftaran rfid memberikan notifikasi jika uid yang didaftarkan sudah terdaftar di akun warga lain', function () {
    // 1. Daftarkan kartu KTP untuk wargaUser
    RfidCard::create([
        'user_id' => $this->wargaUser->id,
        'uid_hash' => RfidCard::hashUid('A1B2C3D4'),
        'ktp_verified_at' => now(),
    ]);

    // 2. otherWargaUser membuka sesi tap mandiri
    $this->actingAs($this->otherWargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertOk();

    // 3. Reader ESP32 tap kartu yang sama (milik wargaUser)
    $response = $this->postJson(
        route('rfid.tap'),
        ['uid' => 'A1B2C3D4'],
        ['Authorization' => 'Bearer smartrw-rfid-device']
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('uid');
    expect($response->json('errors.uid.0'))->toContain($this->wargaUser->name);

    // 4. Polling status oleh otherWargaUser menerima notifikasi konflik
    $statusResponse = $this->actingAs($this->otherWargaUser)
        ->getJson(route('warga.rfid.status'))
        ->assertOk();

    expect($statusResponse->json('notice'))->toContain($this->wargaUser->name);

    // 5. Coba daftarkan via sensor NFC HP juga menampilkan notifikasi nama warga pemilik
    $nfcResponse = $this->actingAs($this->otherWargaUser)
        ->postJson(route('warga.rfid.tap-nfc'), ['uid' => 'A1B2C3D4'])
        ->assertStatus(422);

    expect($nfcResponse->json('message'))->toContain($this->wargaUser->name);
});

test('warga dapat mencabut kartu e-ktp secara mandiri untuk mendaftarkan kartu baru', function () {
    // 1. Daftarkan kartu wargaUser
    RfidCard::create([
        'user_id' => $this->wargaUser->id,
        'uid_hash' => RfidCard::hashUid('A1B2C3D4'),
        'ktp_verified_at' => now(),
    ]);

    expect(RfidCard::where('user_id', $this->wargaUser->id)->exists())->toBeTrue();

    // 2. Warga mencabut kartu mandiri
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.unregister'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(RfidCard::where('user_id', $this->wargaUser->id)->exists())->toBeFalse();

    // 3. Status menjadi belum terverifikasi
    $this->actingAs($this->wargaUser)
        ->getJson(route('warga.rfid.status'))
        ->assertOk()
        ->assertJson(['is_verified' => false]);
});

test('rfid endpoint mendukung route scan, heartbeat, dan header X-Device-Key', function () {
    // 1. Heartbeat route
    $this->getJson(route('rfid.heartbeat'))
        ->assertOk()
        ->assertJson(['status' => 'online']);

    // 2. Open session
    $this->actingAs($this->wargaUser)
        ->postJson(route('warga.rfid.open'))
        ->assertOk();

    // 3. Tap via /api/rfid/scan dengan X-Device-Key
    $response = $this->postJson(
        route('rfid.scan'),
        ['uid' => '11223344'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ktp_registered']);

    // 4. Reset kartu lewat device key
    $reset = $this->postJson(
        route('rfid.tap'),
        ['action' => 'reset_card', 'uid' => '11223344'],
        ['X-Device-Key' => 'smartrw-rfid-device']
    );

    $reset->assertOk()
        ->assertJson(['status' => 'success']);

    expect(RfidCard::where('user_id', $this->wargaUser->id)->exists())->toBeFalse();
});
