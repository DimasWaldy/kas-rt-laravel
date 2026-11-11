<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->rw = Rw::create(['name' => 'RW Domisili Edit', 'is_active' => true]);
    $this->rt = Rt::create(['name' => 'RT 01', 'rw_id' => $this->rw->id, 'is_active' => true]);
    $this->rumah = Rumah::create([
        'kode_rumah' => 'E-01', 'alamat' => 'Jl. Edit Domisili 1',
        'rt_id' => $this->rt->id, 'status' => 'aktif',
    ]);
    $this->user = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->firstOrFail()->id,
        'rt_id' => $this->rt->id,
        'rumah_id' => $this->rumah->id,
        'onboarding_domisili' => false,
        'status_akun' => 'aktif',
        'is_penanggung_jawab_rumah' => false,
    ]);
    $this->warga = Warga::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => $this->user->name,
        'status_verifikasi' => 'terverifikasi',
        'status_domisili' => 'disetujui',
    ]);
});

test('verified resident can claim billing responsibility for own house', function () {
    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'ajukan_pj_iuran' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBe($this->user->id)
        ->and($this->user->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
});

test('verified resident can release billing responsibility', function () {
    $this->rumah->update(['penanggung_jawab_id' => $this->user->id]);
    $this->user->update(['is_penanggung_jawab_rumah' => true]);

    $this->actingAs($this->user->fresh())->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'ajukan_pj_iuran' => '0',
    ])->assertSessionHasNoErrors();

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBeNull()
        ->and($this->user->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
});

test('verified resident cannot steal billing responsibility from another resident', function () {
    $other = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->firstOrFail()->id,
        'rt_id' => $this->rt->id,
        'rumah_id' => $this->rumah->id,
        'status_akun' => 'aktif',
    ]);
    $this->rumah->update(['penanggung_jawab_id' => $other->id]);

    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'ajukan_pj_iuran' => '1',
    ])->assertSessionHasNoErrors();

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBe($other->id)
        ->and($this->user->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
});

test('verified resident can submit a new domicile request that stays pending', function () {
    $newHouse = Rumah::create([
        'kode_rumah' => 'E-02', 'alamat' => 'Jl. Pindahan 2',
        'rt_id' => $this->rt->id, 'status' => 'aktif',
    ]);

    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'rt_id' => $this->rt->id,
        'mode' => 'existing',
        'rumah_id' => $newHouse->id,
    ])->assertSessionHasNoErrors();

    $warga = $this->warga->fresh();
    expect($warga->status_domisili)->toBe('pending')
        ->and($warga->rumah_diajukan_id)->toBe($newHouse->id)
        ->and($this->user->fresh()->rumah_id)->toBe($this->rumah->id);
});

test('verified resident still cannot change house directly without a domicile request', function () {
    $otherHouse = Rumah::create([
        'kode_rumah' => 'E-03', 'alamat' => 'Jl. Privat 3',
        'rt_id' => $this->rt->id, 'status' => 'aktif',
    ]);

    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'rumah_id' => $otherHouse->id,
    ])->assertSessionHasErrors('rumah_id');

    expect($this->user->fresh()->rumah_id)->toBe($this->rumah->id);
});

test('profile page shows editable domicile block for verified resident', function () {
    $response = $this->actingAs($this->user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Data Domisili')
        ->assertSee('penanggung jawab (PJ) iuran', false)
        ->assertSee('Ajukan pindah / ubah domisili rumah')
        ->assertDontSee('Jl. Privat');

    expect($response->getContent())->toContain('name="ajukan_ubah_domisili"');
});

test('officer-assigned pj keeps checkbox checked and preserves status when omitted', function () {
    $this->rumah->update(['penanggung_jawab_id' => $this->user->id]);
    $this->user->update(['is_penanggung_jawab_rumah' => true]);
    $this->warga->update(['ajukan_pj_iuran' => false]);

    $response = $this->actingAs($this->user->fresh())->get(route('profile.edit'))->assertOk();
    expect($response->getContent())->toMatch('/name="ajukan_pj_iuran"[^>]*checked/');

    $this->actingAs($this->user->fresh())->patch(route('profile.update'), [
        'name' => 'Nama Baru Saja',
        'email' => $this->user->email,
    ])->assertSessionHasNoErrors();

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBe($this->user->id)
        ->and($this->user->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
});

test('profile update records audit log when claiming and releasing pj responsibility', function () {
    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'ajukan_pj_iuran' => '1',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->id,
        'auditable_type' => Rumah::class,
        'auditable_id' => $this->rumah->id,
        'event' => 'pj_iuran_ditetapkan',
    ]);

    $this->actingAs($this->user->fresh())->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'ajukan_pj_iuran' => '0',
    ])->assertSessionHasNoErrors();

    expect(AuditLog::where('auditable_type', Rumah::class)
        ->where('auditable_id', $this->rumah->id)
        ->where('event', 'pj_iuran_dilepas')
        ->exists())->toBeTrue();
});

test('verified resident cannot submit domicile request to their current house', function () {
    $this->actingAs($this->user)->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'rt_id' => $this->rt->id,
        'mode' => 'existing',
        'rumah_id' => $this->rumah->id,
    ])->assertSessionHasErrors('rumah_id');

    expect($this->warga->fresh()->status_domisili)->toBe('disetujui');
});

test('domicile move approval clears old house pj and transfers resident cleanly', function () {
    $this->rumah->update(['penanggung_jawab_id' => $this->user->id]);
    $this->user->update(['is_penanggung_jawab_rumah' => true]);

    $newHouse = Rumah::create([
        'kode_rumah' => 'E-04', 'alamat' => 'Jl. Baru 4',
        'rt_id' => $this->rt->id, 'status' => 'aktif',
    ]);
    $officer = User::factory()->create([
        'role_id' => Role::where('name', 'ketua_rt')->firstOrFail()->id,
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);

    $this->actingAs($this->user->fresh())->patch(route('profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'rt_id' => $this->rt->id,
        'mode' => 'existing',
        'rumah_id' => $newHouse->id,
        'ajukan_pj_iuran' => '1',
    ])->assertSessionHasNoErrors();

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBe($this->user->id)
        ->and($newHouse->fresh()->penanggung_jawab_id)->toBeNull();

    $this->actingAs($officer)->patch(route('verifikasi-warga.domisili.setujui', $this->warga))
        ->assertSessionHasNoErrors();

    expect($this->rumah->fresh()->penanggung_jawab_id)->toBeNull()
        ->and($this->rumah->fresh()->status)->toBe('kosong')
        ->and($this->user->fresh()->rumah_id)->toBe($newHouse->id)
        ->and($this->user->fresh()->is_penanggung_jawab_rumah)->toBeTrue()
        ->and($newHouse->fresh()->penanggung_jawab_id)->toBe($this->user->id);
});

test('dashboard hides global cash balance and privileged quick actions from residents', function () {
    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('keamanan.index').'"', false)
        ->assertSee('Menu Cepat')
        ->assertSee('Bayar Iuran');

    $sekretaris = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->firstOrFail()->id,
        'rt_id' => $this->rt->id,
        'status_akun' => 'aktif',
    ]);

    $this->actingAs($sekretaris)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Saldo Kas RT Saat Ini');

    $pending = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->firstOrFail()->id,
        'status_akun' => 'pending_verifikasi',
        'onboarding_domisili' => true,
    ]);

    $this->actingAs($pending)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Saldo Kas RT Saat Ini')
        ->assertDontSee('Menu Cepat')
        ->assertDontSee('href="'.route('keamanan.index').'"', false);
});
