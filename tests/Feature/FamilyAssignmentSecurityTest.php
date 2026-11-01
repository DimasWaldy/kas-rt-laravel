<?php

use App\Models\AuditLog;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW Family', 'is_active' => true]);
    $this->familyRt = Rt::create(['name' => 'RT Family', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->familyOtherRt = Rt::create(['name' => 'RT Other', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->familyHouse = Rumah::create(['kode_rumah' => 'FAMILY-HOUSE', 'rt_id' => $this->familyRt->id, 'status' => 'aktif']);
    $this->familyKk = KartuKeluarga::create(['no_kk' => '1234567890123456', 'rumah_id' => $this->familyHouse->id, 'nama_kepala_keluarga' => 'Kepala Resmi']);
    $roleId = Role::where('name', 'warga')->value('id');
    $this->familyHead = User::factory()->create(['name' => 'Kepala Resmi', 'role_id' => $roleId, 'rt_id' => $this->familyRt->id, 'rumah_id' => $this->familyHouse->id]);
    $this->familyMember = User::factory()->create(['name' => 'Anggota Keluarga', 'role_id' => $roleId, 'rt_id' => $this->familyRt->id, 'rumah_id' => $this->familyHouse->id]);
    foreach ([$this->familyHead, $this->familyMember] as $user) {
        Warga::create([
            'user_id' => $user->id, 'nama_lengkap' => $user->name, 'kartu_keluarga_id' => $this->familyKk->id,
            'status_dalam_kk' => $user->id === $this->familyHead->id ? 'kepala_keluarga' : 'anggota',
            'status_verifikasi' => 'terverifikasi',
        ]);
    }
    $this->familyOfficer = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rt')->value('id'), 'rt_id' => $this->familyRt->id]);
    $this->familyPayload = fn (User $user, array $extra = []) => array_replace([
        'name' => $user->name, 'email' => $user->email, 'phone' => '081234567890',
    ], $extra);
    $this->familyAdminPayload = fn (User $user, array $extra = []) => ($this->familyPayload)($user, array_replace([
        'rumah_id' => $user->rumah_id, 'no_kk' => $this->familyKk->no_kk,
    ], $extra));
});

test('member cannot become a second family head through profile', function () {
    $this->actingAs($this->familyMember)->patch(route('profile.update'), ($this->familyPayload)($this->familyMember, [
        'no_kk' => $this->familyKk->no_kk, 'status_dalam_kk' => 'kepala_keluarga',
    ]))->assertSessionHasErrors('status_dalam_kk');
    expect($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi')
        ->and($this->familyMember->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyKk->wargas()->where('status_dalam_kk', 'kepala_keluarga')->count())->toBe(1);
});

test('profile cannot join an existing kk even when house membership has been approved', function () {
    $newcomer = User::factory()->create(['role_id' => $this->familyMember->role_id, 'rt_id' => $this->familyRt->id, 'rumah_id' => $this->familyHouse->id]);
    Warga::create(['user_id' => $newcomer->id, 'nama_lengkap' => $newcomer->name, 'status_verifikasi' => 'terverifikasi']);
    $this->actingAs($newcomer)->patch(route('profile.update'), ($this->familyPayload)($newcomer, [
        'no_kk' => $this->familyKk->no_kk, 'status_dalam_kk' => 'anggota',
    ]))->assertSessionHasErrors(['no_kk', 'status_dalam_kk']);
    expect($newcomer->fresh()->warga->kartu_keluarga_id)->toBeNull()
        ->and($this->familyKk->fresh()->wargas()->count())->toBe(2);
});

test('profile cannot create or switch to a different kk', function () {
    $this->actingAs($this->familyMember)->patch(route('profile.update'), ($this->familyPayload)($this->familyMember, [
        'no_kk' => '9999999999999999', 'status_dalam_kk' => 'kepala_keluarga',
    ]))->assertSessionHasErrors(['no_kk', 'status_dalam_kk']);
    expect(KartuKeluarga::count())->toBe(1)
        ->and($this->familyMember->fresh()->warga->kartu_keluarga_id)->toBe($this->familyKk->id);
});

test('profile refuses direct family assignment fields without partial updates', function () {
    $this->actingAs($this->familyMember)->patch(route('profile.update'), ($this->familyPayload)($this->familyMember, [
        'name' => 'Nama yang Tidak Boleh Tersimpan', 'kartu_keluarga_id' => $this->familyKk->id,
        'nama_kepala_keluarga' => 'Kepala Palsu', 'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasErrors(['kartu_keluarga_id', 'nama_kepala_keluarga', 'is_kepala_keluarga', 'ganti_kepala_keluarga']);
    expect($this->familyMember->fresh()->name)->toBe('Anggota Keluarga')
        ->and($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi');
});

test('personal name update cannot rename official family head even from cached profile fields', function () {
    $this->actingAs($this->familyHead)->patch(route('profile.update'), ($this->familyPayload)($this->familyHead, [
        'name' => 'Nama Akun Baru', 'no_kk' => $this->familyKk->no_kk, 'status_dalam_kk' => 'kepala_keluarga',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
    expect($this->familyHead->fresh()->name)->toBe('Nama Akun Baru')
        ->and($this->familyHead->fresh()->warga->nama_lengkap)->toBe('Nama Akun Baru')
        ->and($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi');
});

test('profile shows official family data without editable family fields', function () {
    $this->actingAs($this->familyMember)->get(route('profile.edit'))->assertOk()
        ->assertSee($this->familyKk->no_kk)->assertSee('Kepala Resmi')
        ->assertSee('Hubungi pengurus untuk menetapkan atau mengubah KK dan kepala keluarga.')
        ->assertDontSee('name="no_kk"', false)->assertDontSee('name="status_dalam_kk"', false);
});

test('officer cannot assign a second head without explicit confirmation', function (bool $sendKk) {
    $payload = ($this->familyAdminPayload)($this->familyMember, ['name' => 'Tidak Disimpan', 'is_kepala_keluarga' => 1]);
    if (! $sendKk) {
        unset($payload['no_kk']);
    }
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), $payload)
        ->assertSessionHasErrors('is_kepala_keluarga');
    expect($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi')
        ->and($this->familyMember->fresh()->name)->toBe('Anggota Keluarga')
        ->and($this->familyMember->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyKk->wargas()->where('status_dalam_kk', 'kepala_keluarga')->count())->toBe(1);
})->with([true, false]);

test('officer confirmed replacement leaves exactly one head and records the decision', function () {
    $this->familyHead->update(['is_penanggung_jawab_rumah' => true]);
    $this->familyHouse->update(['penanggung_jawab_id' => $this->familyHead->id]);
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.warga.index'));
    expect($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Anggota Keluarga')
        ->and($this->familyMember->fresh()->warga->status_dalam_kk)->toBe('kepala_keluarga')
        ->and($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyKk->wargas()->where('status_dalam_kk', 'kepala_keluarga')->count())->toBe(1)
        ->and($this->familyHouse->fresh()->penanggung_jawab_id)->toBe($this->familyHead->id)
        ->and($this->familyHead->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
    $audit = AuditLog::where('event', 'keluarga_diperbarui')->latest('id')->firstOrFail();
    expect($audit->user_id)->toBe($this->familyOfficer->id)
        ->and($audit->old_values['kepala_lain_ids'])->toContain($this->familyHead->warga->id)
        ->and($audit->new_values['nama_kepala_keluarga'])->toBe('Anggota Keluarga');
});

test('family member edits by officer cannot rename the existing head', function () {
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'nama_kepala_keluarga' => 'Nama Anggota Salah',
    ]))->assertSessionHasNoErrors();
    expect($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi');
});

test('replacement confirmation without selecting a new head is rejected', function () {
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasErrors('ganti_kepala_keluarga');
    expect($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('kepala_keluarga');
});

test('other rt officers ordinary residents and inactive officers cannot replace head', function () {
    $otherOfficer = User::factory()->create(['role_id' => $this->familyOfficer->role_id, 'rt_id' => $this->familyOtherRt->id]);
    $this->actingAs($otherOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertNotFound();
    $this->actingAs($this->familyMember)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertForbidden();
    $this->familyOfficer->update(['status_akun' => 'ditolak']);
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertForbidden();
    expect($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('kepala_keluarga');
});

test('new kk for an ordinary member requires the actual head name', function () {
    $payload = ['name' => 'Anggota Baru', 'email' => 'anggota-new-kk@example.test', 'password' => 'password',
        'rumah_id' => $this->familyHouse->id, 'no_kk' => '2222222222222222'];
    $this->actingAs($this->familyOfficer)->post(route('admin.warga.store'), $payload)->assertSessionHasErrors('nama_kepala_keluarga');
    expect(User::where('email', $payload['email'])->exists())->toBeFalse()
        ->and(KartuKeluarga::where('no_kk', $payload['no_kk'])->exists())->toBeFalse();
    $this->post(route('admin.warga.store'), $payload + ['nama_kepala_keluarga' => 'Kepala Sesuai Dokumen'])->assertSessionHasNoErrors();
    $user = User::where('email', $payload['email'])->firstOrFail();
    expect($user->warga->status_dalam_kk)->toBe('anggota')
        ->and($user->warga->kartuKeluarga->nama_kepala_keluarga)->toBe('Kepala Sesuai Dokumen');
});

test('creating a second head must also confirm replacement and cannot leave a partial account', function () {
    $payload = ['name' => 'Kepala Baru', 'email' => 'kepala-baru@example.test', 'password' => 'password',
        'rumah_id' => $this->familyHouse->id, 'no_kk' => $this->familyKk->no_kk, 'is_kepala_keluarga' => 1];
    $this->actingAs($this->familyOfficer)->post(route('admin.warga.store'), $payload)->assertSessionHasErrors('is_kepala_keluarga');
    expect(User::where('email', $payload['email'])->exists())->toBeFalse();
    $this->post(route('admin.warga.store'), $payload + ['ganti_kepala_keluarga' => 1])->assertSessionHasNoErrors();
    expect($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Baru')
        ->and($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyKk->wargas()->where('status_dalam_kk', 'kepala_keluarga')->count())->toBe(1);
});

test('existing kk from another house cannot be adopted when resident has no house', function () {
    $unassigned = User::factory()->create(['role_id' => $this->familyMember->role_id, 'rt_id' => $this->familyRt->id, 'rumah_id' => null]);
    Warga::create(['user_id' => $unassigned->id, 'nama_lengkap' => $unassigned->name]);
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $unassigned), ($this->familyPayload)($unassigned, [
        'no_kk' => $this->familyKk->no_kk, 'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasErrors('no_kk');
    expect($unassigned->fresh()->warga->kartu_keluarga_id)->toBeNull()
        ->and($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi');
});

test('family validation failure rolls back a house created by the same administrative request', function () {
    $this->actingAs($this->familyOfficer)->post(route('admin.warga.store'), [
        'name' => 'Ditolak', 'email' => 'rollback-family@example.test', 'password' => 'password',
        'rumah_kode' => 'SHOULD-ROLL-BACK', 'rumah_alamat' => 'Alamat Baru',
        'no_kk' => $this->familyKk->no_kk, 'is_kepala_keluarga' => 1,
    ])->assertSessionHasErrors('no_kk');
    expect(Rumah::where('kode_rumah', 'SHOULD-ROLL-BACK')->exists())->toBeFalse()
        ->and(User::where('email', 'rollback-family@example.test')->exists())->toBeFalse();
});

test('privileged users also cannot use their personal profile to assign family membership', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'super_admin')->value('id')]);
    $this->actingAs($admin)->patch(route('profile.update'), ($this->familyPayload)($admin, [
        'no_kk' => $this->familyKk->no_kk, 'status_dalam_kk' => 'kepala_keluarga',
    ]))->assertSessionHasErrors(['no_kk', 'status_dalam_kk']);
    expect($admin->fresh()->warga)->toBeNull()->and($this->familyKk->fresh()->nama_kepala_keluarga)->toBe('Kepala Resmi');
});

test('legacy duplicate heads can be resolved through explicit officer confirmation', function () {
    $duplicate = User::factory()->create(['role_id' => $this->familyMember->role_id, 'rt_id' => $this->familyRt->id, 'rumah_id' => $this->familyHouse->id]);
    Warga::create(['user_id' => $duplicate->id, 'nama_lengkap' => $duplicate->name, 'kartu_keluarga_id' => $this->familyKk->id, 'status_dalam_kk' => 'kepala_keluarga']);
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasNoErrors();
    expect($duplicate->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('anggota')
        ->and($this->familyKk->wargas()->where('status_dalam_kk', 'kepala_keluarga')->count())->toBe(1);
});

test('legacy kk without house cannot be claimed from another residents territory', function () {
    $kk = KartuKeluarga::create(['no_kk' => '3333333333333333', 'nama_kepala_keluarga' => 'Kepala Wilayah Lain']);
    $resident = User::factory()->create(['role_id' => $this->familyMember->role_id, 'rt_id' => $this->familyOtherRt->id]);
    Warga::create(['user_id' => $resident->id, 'nama_lengkap' => $resident->name, 'kartu_keluarga_id' => $kk->id, 'status_dalam_kk' => 'kepala_keluarga']);
    $this->actingAs($this->familyOfficer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'no_kk' => $kk->no_kk, 'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertSessionHasErrors('no_kk');
    expect($this->familyMember->fresh()->warga->kartu_keluarga_id)->toBe($this->familyKk->id)
        ->and($kk->fresh()->rumah_id)->toBeNull();
});

test('rw officer cannot approve family changes in a different rw', function () {
    $rw = Rw::create(['name' => 'Other RW Family', 'is_active' => true]);
    $rt = Rt::create(['name' => 'RT Other RW', 'rw_id' => $rw->id, 'is_active' => true]);
    $officer = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rw')->value('id'), 'rt_id' => $rt->id]);
    $this->actingAs($officer)->patch(route('admin.warga.update', $this->familyMember), ($this->familyAdminPayload)($this->familyMember, [
        'is_kepala_keluarga' => 1, 'ganti_kepala_keluarga' => 1,
    ]))->assertNotFound();
    expect($this->familyHead->fresh()->warga->status_dalam_kk)->toBe('kepala_keluarga');
});
