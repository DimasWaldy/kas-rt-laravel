<?php

use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->rwRfid = Rw::create(['name' => 'RW RFID', 'is_active' => true]);
    $this->rtRfid = Rt::create(['rw_id' => $this->rwRfid->id, 'name' => 'RT 01', 'is_active' => true]);
    $this->otherRtRfid = Rt::create(['rw_id' => $this->rwRfid->id, 'name' => 'RT 02', 'is_active' => true]);
    $this->makeRfidUser = fn ($role = 'warga', $rt = null) => User::factory()->create([
        'role_id' => Role::where('name', $role)->value('id'),
        'rt_id' => ($rt ?? $this->rtRfid)->id,
        'status_akun' => 'aktif',
    ]);
    $this->operatorRfid = ($this->makeRfidUser)('ketua_rt');
    $this->residentRfid = ($this->makeRfidUser)();
    $this->activityRfid = Kegiatan::create([
        'rw_id' => $this->rwRfid->id, 'rt_id' => $this->rtRfid->id,
        'created_by' => $this->operatorRfid->id, 'nama' => 'Musyawarah RFID',
        'tanggal_mulai' => now()->subHour(), 'tanggal_selesai' => now()->addHours(2),
        'status' => 'akan_datang',
    ]);
    $this->openRfid = function () {
        $response = $this->actingAs($this->operatorRfid)
            ->post(route('kegiatan.rfid.open', $this->activityRfid));
        $response->assertRedirect()->assertSessionHas('rfid_token');

        return session('rfid_token');
    };
    $this->tapRfid = fn ($token, $uid = 'A1B2C3D4') => $this->postJson(route('rfid.tap'), ['uid' => $uid], ['Authorization' => 'Bearer '.$token]);
});

test('rfid pairing lalu tap mencatat hadir sekali tanpa mengubah konfirmasi lama', function () {
    $token = ($this->openRfid)();
    expect(RfidSession::first()->token_hash)->toBe(hash('sha256', $token))->not->toBe($token);
    $this->actingAs($this->operatorRfid)->get(route('kegiatan.rfid.show', $this->activityRfid))
        ->assertOk()->assertSee('Token alat')->assertSee($token)->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertSessionHasNoErrors();
    ($this->tapRfid)($token)->assertOk()->assertJsonPath('status', 'registered');
    expect(KegiatanHadir::count())->toBe(0)
        ->and(RfidCard::first()->uid_hash)->toBe(RfidCard::hashUid('A1B2C3D4'))
        ->and(RfidSession::first()->enrollment_user_id)->toBeNull();

    $confirmed = now()->subMinutes(20)->startOfSecond();
    KegiatanHadir::create(['kegiatan_id' => $this->activityRfid->id, 'user_id' => $this->residentRfid->id, 'konfirmasi_at' => $confirmed, 'catatan' => 'Akan hadir']);
    ($this->tapRfid)($token, 'a1b2c3d4')->assertOk()->assertJsonPath('status', 'present');
    $firstTap = KegiatanHadir::first()->rfid_hadir_at->toDateTimeString();
    $this->travel(10)->seconds();
    ($this->tapRfid)($token)->assertOk()->assertJsonPath('status', 'already_present');
    expect(KegiatanHadir::count())->toBe(1)
        ->and(KegiatanHadir::first()->rfid_hadir_at->toDateTimeString())->toBe($firstTap)
        ->and(KegiatanHadir::first()->konfirmasi_at->equalTo($confirmed))->toBeTrue()
        ->and(KegiatanHadir::first()->catatan)->toBe('Akan hadir');
    $this->getJson(route('kegiatan.rfid.status', $this->activityRfid))->assertOk()
        ->assertJsonPath('count', 1)->assertJsonPath('attendees.0.name', $this->residentRfid->name)
        ->assertDontSee($token)->assertDontSee('A1B2C3D4')->assertDontSee($this->residentRfid->email);
});

test('rfid token wajib dan tidak bisa diganti autentikasi browser', function () {
    ($this->openRfid)();
    $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'])->assertUnauthorized();
    ($this->tapRfid)(str_repeat('a', 64))->assertUnauthorized();
    expect(KegiatanHadir::count())->toBe(0);
});

test('rfid token rotasi tutup dan kedaluwarsa membatalkan akses alat', function () {
    $old = ($this->openRfid)();
    $new = ($this->openRfid)();
    ($this->tapRfid)($old)->assertUnauthorized();
    $this->post(route('kegiatan.rfid.close', $this->activityRfid))->assertRedirect();
    ($this->tapRfid)($new)->assertUnauthorized();
    $new = ($this->openRfid)();
    $this->travel(6)->hours();
    ($this->tapRfid)($new)->assertUnauthorized();
});

test('rfid menolak kartu tidak dikenal dan format uid invalid', function () {
    $token = ($this->openRfid)();
    ($this->tapRfid)($token)->assertUnprocessable();
    foreach (['', '123', 'A1:B2:C3:D4', '<script>', str_repeat('F', 22)] as $uid) {
        ($this->tapRfid)($token, $uid)->assertUnprocessable()->assertJsonValidationErrors('uid');
    }
    expect(KegiatanHadir::count())->toBe(0);
});

test('rfid warga dan pengurus rt lain tidak bisa mengelola sesi kartu maupun feed', function () {
    foreach ([$this->residentRfid, ($this->makeRfidUser)('ketua_rt', $this->otherRtRfid)] as $actor) {
        $this->actingAs($actor)->get(route('kegiatan.rfid.show', $this->activityRfid))->assertForbidden();
        $this->getJson(route('kegiatan.rfid.status', $this->activityRfid))->assertForbidden();
        foreach (['open', 'close', 'cancel-enrollment', 'enroll', 'revoke'] as $action) {
            $this->post(route('kegiatan.rfid.'.$action, $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertForbidden();
        }
    }
    expect(RfidSession::count())->toBe(0);
});

test('rfid pengurus rw hanya wilayah sendiri dan wajib punya assignment', function () {
    $rwOfficial = ($this->makeRfidUser)('ketua_rw');
    $this->actingAs($rwOfficial)->get(route('kegiatan.rfid.show', $this->activityRfid))->assertOk();
    $otherRw = Rw::create(['name' => 'RW lain', 'is_active' => true]);
    $otherRt = Rt::create(['rw_id' => $otherRw->id, 'name' => 'RT lain', 'is_active' => true]);
    $rwOfficial->update(['rt_id' => $otherRt->id]);
    $this->actingAs($rwOfficial->fresh())->post(route('kegiatan.rfid.open', $this->activityRfid))->assertForbidden();
    $rwOfficial->update(['rt_id' => null]);
    $this->actingAs($rwOfficial->fresh())->post(route('kegiatan.rfid.open', $this->activityRfid))->assertForbidden();
    $admin = ($this->makeRfidUser)('super_admin');
    $admin->update(['rt_id' => null]);
    $this->actingAs($admin->fresh())->get(route('kegiatan.rfid.show', $this->activityRfid))->assertOk();
});

test('rfid rt tidak bisa mengelola kegiatan rw sedangkan pengurus rw bisa', function () {
    $this->activityRfid->update(['rt_id' => null]);
    $this->actingAs($this->operatorRfid)->post(route('kegiatan.rfid.open', $this->activityRfid))->assertForbidden();
    $this->actingAs(($this->makeRfidUser)('ketua_rw'))->post(route('kegiatan.rfid.open', $this->activityRfid))->assertRedirect()->assertSessionHas('rfid_token');
});

test('rfid menolak enroll dan tap warga luar rt tanpa rt atau akun tidak aktif', function () {
    $token = ($this->openRfid)();
    $other = ($this->makeRfidUser)('warga', $this->otherRtRfid);
    foreach (['other', 'null', 'inactive'] as $case) {
        if ($case === 'null') {
            $other->update(['rt_id' => null]);
        }
        if ($case === 'inactive') {
            $other->update(['rt_id' => $this->rtRfid->id, 'status_akun' => 'pending_verifikasi']);
        }
        $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $other->id])->assertNotFound();
        RfidCard::updateOrCreate(['user_id' => $other->id], ['uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
        ($this->tapRfid)($token)->assertNotFound();
        $this->post(route('kegiatan.rfid.revoke', $this->activityRfid), ['user_id' => $other->id])->assertNotFound();
    }
    expect(KegiatanHadir::count())->toBe(0);
});

test('rfid kartu tidak bisa dipasangkan ulang atau diganti diam diam', function () {
    $token = ($this->openRfid)();
    RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertSessionHasErrors('rfid');
    $other = ($this->makeRfidUser)();
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $other->id])->assertSessionHasNoErrors();
    ($this->tapRfid)($token)->assertUnprocessable();
    expect(RfidCard::count())->toBe(1)->and(RfidCard::first()->user_id)->toBe($this->residentRfid->id);
});

test('rfid pendaftaran kedaluwarsa dan batal tidak membuat kartu', function () {
    $token = ($this->openRfid)();
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertSessionHasNoErrors();
    $this->travel(3)->minutes();
    ($this->tapRfid)($token)->assertUnprocessable();
    $this->post(route('kegiatan.rfid.cancel-enrollment', $this->activityRfid))->assertRedirect();
    ($this->tapRfid)($token)->assertUnprocessable();
    expect(RfidCard::count())->toBe(0)->and(KegiatanHadir::count())->toBe(0);
});

test('rfid pencabutan kartu memblokir tap tanpa menghapus riwayat', function () {
    $token = ($this->openRfid)();
    RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    ($this->tapRfid)($token)->assertOk();
    $this->post(route('kegiatan.rfid.revoke', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertRedirect();
    ($this->tapRfid)($token)->assertUnprocessable();
    expect(RfidCard::count())->toBe(0)->and(KegiatanHadir::count())->toBe(1);
});

test('rfid sesi menolak operator yang kehilangan izin setelah sesi dibuka', function () {
    $token = ($this->openRfid)();
    $this->operatorRfid->update(['role_id' => Role::where('name', 'warga')->value('id')]);
    ($this->tapRfid)($token)->assertForbidden();
});

test('rfid kegiatan mendatang hanya menerima pendaftaran bukan absensi', function () {
    $this->activityRfid->update(['tanggal_mulai' => now()->addHour()]);
    $token = ($this->openRfid)();
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertSessionHasNoErrors();
    ($this->tapRfid)($token)->assertOk()->assertJsonPath('status', 'registered');
    ($this->tapRfid)($token)->assertConflict();
    expect(KegiatanHadir::count())->toBe(0);
});

test('rfid kegiatan selesai atau dibatalkan menolak tap dan pembukaan sesi', function ($status) {
    $token = ($this->openRfid)();
    $this->activityRfid->update(['status' => $status]);
    ($this->tapRfid)($token)->assertConflict();
    $this->post(route('kegiatan.rfid.open', $this->activityRfid))->assertSessionHasErrors('rfid');
})->with(['selesai', 'dibatalkan']);

test('rfid tamu tidak bisa membaca dashboard dan konfirmasi biasa bukan hadir fisik', function () {
    $this->get(route('kegiatan.rfid.show', $this->activityRfid))->assertRedirect(route('login'));
    $this->actingAs($this->residentRfid)->post(route('kegiatan.hadir', $this->activityRfid))->assertRedirect();
    expect(KegiatanHadir::first()->rfid_hadir_at)->toBeNull();
    $this->actingAs($this->operatorRfid)->getJson(route('kegiatan.rfid.status', $this->activityRfid))->assertJsonPath('count', 0);
});

test('rfid endpoint tap dibatasi lajunya', function () {
    for ($i = 0; $i < 120; $i++) {
        $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'])->assertUnauthorized();
    }
    $this->postJson(route('rfid.tap'), ['uid' => 'A1B2C3D4'])->assertTooManyRequests();
});

test('rfid tap tidak percaya identitas dan waktu yang dikirim alat', function () {
    $token = ($this->openRfid)();
    $other = ($this->makeRfidUser)();
    RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    $this->postJson(route('rfid.tap'), [
        'uid' => 'A1B2C3D4', 'user_id' => $other->id,
        'kegiatan_id' => 999999, 'rfid_hadir_at' => '2000-01-01 00:00:00',
        'status_akun' => 'aktif',
    ], ['Authorization' => 'Bearer '.$token])->assertOk()->assertJsonPath('status', 'present');
    $this->assertDatabaseHas('kegiatan_hadirs', ['user_id' => $this->residentRfid->id, 'kegiatan_id' => $this->activityRfid->id]);
    $this->assertDatabaseMissing('kegiatan_hadirs', ['user_id' => $other->id]);
    expect(KegiatanHadir::first()->rfid_hadir_at->isToday())->toBeTrue();
    $this->actingAs($this->residentRfid)->get(route('kegiatan.show', $this->activityRfid))
        ->assertOk()->assertSee('Kehadiran Anda tercatat melalui RFID')->assertSee('Hadir RFID:')
        ->assertDontSee('Anda sudah konfirmasi hadir');
    $this->post(route('kegiatan.hadir', $this->activityRfid), ['catatan' => 'Catatan tambahan'])->assertRedirect();
    expect(KegiatanHadir::first()->rfid_hadir_at)->not->toBeNull();
});

test('rfid kegiatan rw menerima warga rt lain dalam rw tetapi menolak rw luar', function () {
    $this->activityRfid->update(['rt_id' => null]);
    $this->operatorRfid = ($this->makeRfidUser)('ketua_rw');
    $token = ($this->openRfid)();
    $resident = ($this->makeRfidUser)('warga', $this->otherRtRfid);
    RfidCard::create(['user_id' => $resident->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    ($this->tapRfid)($token)->assertOk()->assertJsonPath('status', 'present');
    $otherRw = Rw::create(['name' => 'RW terpisah', 'is_active' => true]);
    $otherRt = Rt::create(['rw_id' => $otherRw->id, 'name' => 'RT terpisah', 'is_active' => true]);
    $resident->update(['rt_id' => $otherRt->id]);
    ($this->tapRfid)($token)->assertNotFound();
});

test('rfid menolak assignment warga yang berubah saat menunggu pairing', function () {
    $token = ($this->openRfid)();
    $this->post(route('kegiatan.rfid.enroll', $this->activityRfid), ['user_id' => $this->residentRfid->id])->assertSessionHasNoErrors();
    $this->residentRfid->update(['rt_id' => $this->otherRtRfid->id]);
    ($this->tapRfid)($token)->assertNotFound();
    expect(RfidCard::count())->toBe(0);
});

test('rfid otomatis menolak tap ketika jadwal kegiatan berakhir', function () {
    $token = ($this->openRfid)();
    $this->travel(3)->hours();
    ($this->tapRfid)($token)->assertConflict();
    $this->actingAs($this->operatorRfid)->getJson(route('kegiatan.rfid.status', $this->activityRfid))
        ->assertOk()->assertJsonPath('open', false);
});

test('rfid fingerprint tersembunyi dan batas satu kartu per warga ditegakkan database', function () {
    $card = RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    expect($card->toArray())->not->toHaveKey('uid_hash');
    expect(fn () => RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('11223344')]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('rfid operator aktif tidak memerlukan verifikasi email tetapi izin tetap wajib', function () {
    $this->operatorRfid->email_verified_at = null;
    $this->operatorRfid->save();
    $token = ($this->openRfid)();
    RfidCard::create(['user_id' => $this->residentRfid->id, 'uid_hash' => RfidCard::hashUid('A1B2C3D4')]);
    ($this->tapRfid)($token)->assertOk()->assertJsonPath('status', 'present');
    $this->operatorRfid->update(['status_akun' => 'ditolak']);
    ($this->tapRfid)($token)->assertForbidden();
});
