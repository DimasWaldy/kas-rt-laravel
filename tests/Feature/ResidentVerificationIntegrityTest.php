<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use App\Notifications\WargaTerverifikasi;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Notification::fake();
    Storage::fake('local');
    $rw = Rw::create(['name' => 'RW Integrity', 'is_active' => true]);
    $otherRw = Rw::create(['name' => 'RW Outside', 'is_active' => true]);
    $this->rtIntegrity = Rt::create(['name' => 'RT Integrity', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->rtSibling = Rt::create(['name' => 'RT Sibling', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->rtOutside = Rt::create(['name' => 'RT Outside', 'rw_id' => $otherRw->id, 'is_active' => true]);
    $this->operator = User::factory()->create(['role_id' => Role::where('name', 'super_admin')->value('id')]);
    $this->rtOfficer = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rt')->value('id'), 'rt_id' => $this->rtIntegrity->id]);
    $this->rwOfficer = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rw')->value('id'), 'rt_id' => $this->rtIntegrity->id]);
    $this->residentData = ['name' => 'Warga Wilayah Valid', 'email' => 'integrity@example.test', 'password' => 'password', 'phone' => '081234567890'];
    $this->pendingResident = function (array $documents = []) {
        $user = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'),
            'rt_id' => $this->rtIntegrity->id, 'status_akun' => 'pending_verifikasi', 'onboarding_domisili' => true]);

        return Warga::create(['user_id' => $user->id, 'nama_lengkap' => $user->name, 'status_verifikasi' => 'pending'] + $documents);
    };
});

test('global operator cannot create active resident or new house without an rt', function (string $role, bool $newHouse) {
    $this->operator->update(['role_id' => Role::where('name', $role)->value('id')]);
    $payload = $this->residentData + ($newHouse ? ['rumah_kode' => 'NO-TERRITORY', 'rumah_alamat' => 'Alamat tanpa wilayah'] : []);
    $this->actingAs($this->operator->fresh())->post(route('admin.warga.store'), $payload)->assertSessionHasErrors('rt_id');
    expect(User::where('email', $payload['email'])->exists())->toBeFalse()->and(Rumah::count())->toBe(0)->and(Warga::count())->toBe(0);
})->with([['super_admin', true], ['super_admin', false], ['admin', true]]);

test('global creation puts active resident and new house in chosen rt visible to its officer', function () {
    $this->actingAs($this->operator)->post(route('admin.warga.store'), $this->residentData + [
        'rt_id' => $this->rtIntegrity->id, 'rumah_kode' => 'INTEGRITY-01', 'rumah_alamat' => 'Alamat sesuai RT',
    ])->assertRedirect(route('admin.warga.index'))->assertSessionHasNoErrors();
    $user = User::where('email', $this->residentData['email'])->sole();
    expect($user->rt_id)->toBe($this->rtIntegrity->id)->and((int) $user->rumah->rt_id)->toBe($this->rtIntegrity->id)
        ->and($user->status_akun)->toBe('aktif')->and($user->warga->status_verifikasi)->toBe('terverifikasi')
        ->and($user->onboarding_domisili)->toBeFalse();
    $this->actingAs($this->rtOfficer)->get(route('admin.warga.index'))->assertOk()->assertSee($user->name);
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('global creation without house still requires and retains chosen rt', function () {
    $this->actingAs($this->operator)->post(route('admin.warga.store'), $this->residentData + ['rt_id' => $this->rtSibling->id])
        ->assertSessionHasNoErrors();
    $user = User::where('email', $this->residentData['email'])->sole();
    expect($user->rt_id)->toBe($this->rtSibling->id)->and($user->rumah_id)->toBeNull()->and($user->status_akun)->toBe('aktif');
});

test('invalid inactive rt or inactive parent rw cannot receive a resident or new house', function (string $case) {
    $rtId = $this->rtIntegrity->id;
    if ($case === 'missing') {
        $rtId = 999999;
    }
    if ($case === 'rt') {
        $this->rtIntegrity->update(['is_active' => false]);
    }
    if ($case === 'rw') {
        $this->rtIntegrity->rw->update(['is_active' => false]);
    }
    $this->actingAs($this->operator)->post(route('admin.warga.store'), $this->residentData + ['rt_id' => $rtId, 'rumah_kode' => 'INVALID-RT'])
        ->assertSessionHasErrors('rt_id');
    expect(Rumah::count())->toBe(0)->and(User::where('email', $this->residentData['email'])->exists())->toBeFalse();
})->with(['missing', 'rt', 'rw']);

test('existing house derives rt but cannot be silently moved by a conflicting selection or code reuse', function (bool $byCode) {
    $house = Rumah::create(['kode_rumah' => 'MATCH-RT', 'rt_id' => $this->rtIntegrity->id, 'status' => 'aktif']);
    $selection = $byCode ? ['rumah_kode' => 'match-rt'] : ['rumah_id' => $house->id];
    $this->actingAs($this->operator)->post(route('admin.warga.store'), $this->residentData + $selection + ['rt_id' => $this->rtSibling->id])
        ->assertSessionHasErrors('rt_id');
    expect((int) $house->fresh()->rt_id)->toBe($this->rtIntegrity->id)->and(User::where('email', $this->residentData['email'])->exists())->toBeFalse();
    $this->post(route('admin.warga.store'), $this->residentData + $selection)->assertSessionHasNoErrors();
    $user = User::where('email', $this->residentData['email'])->sole();
    expect($user->rt_id)->toBe($this->rtIntegrity->id)->and($user->rumah_id)->toBe($house->id)->and(Rumah::count())->toBe(1);
})->with([false, true]);

test('orphan inactive and inactive territory houses are rejected for new or edited residents', function (string $case) {
    $house = Rumah::create(['kode_rumah' => 'UNSUITABLE', 'rt_id' => $case === 'orphan' ? null : $this->rtIntegrity->id,
        'status' => $case === 'inactive' ? 'nonaktif' : 'aktif']);
    if ($case === 'territory') {
        $this->rtIntegrity->update(['is_active' => false]);
    }
    $error = $case === 'territory' ? 'rt_id' : 'rumah_id';
    $this->actingAs($this->operator)->post(route('admin.warga.store'), $this->residentData + ['rumah_id' => $house->id])->assertSessionHasErrors($error);
    $user = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'), 'rt_id' => $this->rtSibling->id]);
    $this->patch(route('admin.warga.update', $user), ['name' => $user->name, 'email' => $user->email, 'rumah_id' => $house->id])->assertSessionHasErrors($error);
    expect($user->fresh()->rt_id)->toBe($this->rtSibling->id)->and($user->fresh()->rumah_id)->toBeNull()
        ->and(User::where('email', $this->residentData['email'])->exists())->toBeFalse();
})->with(['orphan', 'inactive', 'territory']);

test('rt officer inherits own territory and cannot submit a different rt', function () {
    $this->actingAs($this->rtOfficer)->post(route('admin.warga.store'), $this->residentData + ['rt_id' => $this->rtSibling->id, 'rumah_kode' => 'OWN-RT'])
        ->assertSessionHasErrors('rt_id');
    $this->post(route('admin.warga.store'), $this->residentData + ['rumah_kode' => 'OWN-RT'])->assertSessionHasNoErrors();
    expect(User::where('email', $this->residentData['email'])->sole()->rt_id)->toBe($this->rtIntegrity->id);
});

test('rw officer can select sibling rt but cannot select or import residents from another rw', function () {
    $this->actingAs($this->rwOfficer)->post(route('admin.warga.store'), $this->residentData + ['rt_id' => $this->rtOutside->id, 'rumah_kode' => 'RW-HOUSE'])
        ->assertSessionHasErrors('rt_id');
    $outside = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'), 'rt_id' => $this->rtOutside->id]);
    $this->get(route('admin.warga.edit', $outside))->assertNotFound();
    $this->patch(route('admin.warga.update', $outside), ['name' => $outside->name, 'email' => $outside->email, 'rt_id' => $this->rtIntegrity->id])
        ->assertNotFound();
    $this->post(route('admin.warga.store'), $this->residentData + ['rt_id' => $this->rtSibling->id, 'rumah_kode' => 'RW-HOUSE'])->assertSessionHasNoErrors();
    expect(User::where('email', $this->residentData['email'])->sole()->rt_id)->toBe($this->rtSibling->id)
        ->and($outside->fresh()->rt_id)->toBe($this->rtOutside->id);
});

test('global edit repairs unassigned resident only with a valid rt and rolls back new house on failure', function () {
    $user = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id')]);
    $payload = ['name' => $user->name, 'email' => $user->email, 'rumah_kode' => 'REPAIR-RT'];
    $this->actingAs($this->operator)->patch(route('admin.warga.update', $user), $payload)->assertSessionHasErrors('rt_id');
    expect(Rumah::count())->toBe(0)->and($user->fresh()->rt_id)->toBeNull();
    $this->patch(route('admin.warga.update', $user), $payload + ['rt_id' => $this->rtIntegrity->id])->assertSessionHasNoErrors();
    expect($user->fresh()->rt_id)->toBe($this->rtIntegrity->id)->and((int) $user->fresh()->rumah->rt_id)->toBe($this->rtIntegrity->id);
});

test('all administrative resident forms expose only allowed active territory and houses', function () {
    $orphan = Rumah::create(['kode_rumah' => 'HIDDEN-ORPHAN', 'status' => 'aktif']);
    $outside = Rumah::create(['kode_rumah' => 'HIDDEN-OUTSIDE', 'rt_id' => $this->rtOutside->id, 'status' => 'aktif']);
    $inactive = Rt::create(['name' => 'HIDDEN-INACTIVE', 'rw_id' => $this->rtIntegrity->rw_id, 'is_active' => false]);
    $resident = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'), 'rt_id' => $this->rtIntegrity->id]);
    foreach (['admin.warga.index', 'admin.warga.create', 'admin.warga.edit'] as $route) {
        $this->actingAs($this->rwOfficer)->get(route($route, $route === 'admin.warga.edit' ? $resident : []))->assertOk()
            ->assertSee('name="rt_id"', false)->assertSee('RT Sibling')->assertDontSee('RT Outside')
            ->assertDontSee($orphan->kode_rumah)->assertDontSee($outside->kode_rumah)->assertDontSee($inactive->name);
    }
});

test('verification by document rejects empty or missing uploads without activating account', function (bool $missingPath) {
    $warga = ($this->pendingResident)($missingPath ? ['dokumen_kk' => 'missing/kk.pdf', 'dokumen_ktp' => 'missing/ktp.pdf'] : []);
    $this->actingAs($this->rtOfficer)->get(route('verifikasi-warga.show', $warga))->assertOk()
        ->assertSee('Tidak ada unggahan KTP/KK yang tersedia')->assertSee('value="dokumen" disabled', false);
    $this->patch(route('verifikasi-warga.verifikasi', $warga), ['metode_verifikasi' => 'dokumen', 'dokumen_ktp' => 'forged.pdf'])
        ->assertSessionHasErrors('metode_verifikasi');
    expect($warga->fresh()->status_verifikasi)->toBe('pending')->and($warga->fresh()->metode_verifikasi)->toBeNull()
        ->and($warga->fresh()->diverifikasi_at)->toBeNull()->and($warga->user->fresh()->status_akun)->toBe('pending_verifikasi');
    Notification::assertNothingSent();
})->with([false, true]);

test('document review accepts actual ktp or kk and exposes only files still available', function (string $type) {
    $path = 'verifikasi/'.$type.'.pdf';
    Storage::disk('local')->put($path, 'synthesized document');
    $warga = ($this->pendingResident)(['dokumen_'.$type => $path]);
    $this->actingAs($this->rtOfficer)->get(route('verifikasi-warga.show', $warga))->assertOk()
        ->assertDontSee('value="dokumen" disabled', false)->assertSee(route('verifikasi-warga.dokumen', [$warga, $type]), false);
    $this->get(route('verifikasi-warga.dokumen', [$warga, $type]))->assertOk();
    $this->patch(route('verifikasi-warga.verifikasi', $warga), ['metode_verifikasi' => 'dokumen'])->assertSessionHasNoErrors();
    expect($warga->fresh()->metode_verifikasi)->toBe('dokumen')->and($warga->user->fresh()->status_akun)->toBe('aktif');
    Notification::assertSentTo($warga->user, WargaTerverifikasi::class);
})->with(['ktp', 'kk']);

test('file removed after opening review is checked again before approval', function () {
    Storage::disk('local')->put('verifikasi/deleted.pdf', 'temporary document');
    $warga = ($this->pendingResident)(['dokumen_ktp' => 'verifikasi/deleted.pdf']);
    $this->actingAs($this->rtOfficer)->get(route('verifikasi-warga.show', $warga))->assertOk()->assertDontSee('value="dokumen" disabled', false);
    Storage::disk('local')->delete('verifikasi/deleted.pdf');
    $this->patch(route('verifikasi-warga.verifikasi', $warga), ['metode_verifikasi' => 'dokumen'])->assertSessionHasErrors('metode_verifikasi');
    expect($warga->fresh()->status_verifikasi)->toBe('pending');
    Notification::assertNothingSent();
});

test('face to face verification remains available without uploads and records actual method', function () {
    $warga = ($this->pendingResident)();
    $this->actingAs($this->rtOfficer)->patch(route('verifikasi-warga.verifikasi', $warga), ['metode_verifikasi' => 'tatap_muka'])
        ->assertSessionHasNoErrors();
    expect($warga->fresh()->status_verifikasi)->toBe('terverifikasi')->and($warga->fresh()->metode_verifikasi)->toBe('tatap_muka');
});
