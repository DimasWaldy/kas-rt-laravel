<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\User;
use App\Models\Warga;
use App\Models\Tagihan;
use App\Http\Middleware\EnsureInitialDomicile;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Notification::fake();
    $this->rwOnboard = Rw::create(['name' => 'RW Onboard', 'is_active' => true]);
    $this->rtOnboard = Rt::create(['name' => 'RT A', 'rw_id' => $this->rwOnboard->id, 'is_active' => true]);
    $this->rtOther = Rt::create(['name' => 'RT B', 'rw_id' => $this->rwOnboard->id, 'is_active' => true]);
    $this->officer = User::factory()->create(['role_id' => Role::where('name', 'ketua_rt')->value('id'), 'rt_id' => $this->rtOnboard->id]);
    $this->superadmin = User::factory()->create(['role_id' => Role::where('name', 'super_admin')->value('id')]);
    $this->registerResident = function () {
        Auth::logout();
        $this->post(route('register'), [
            'name' => 'Warga Baru Onboard', 'email' => 'onboard@example.test', 'phone' => '081234567890',
            'password' => 'password', 'password_confirmation' => 'password',
            'role_id' => $this->superadmin->role_id, 'status_akun' => 'aktif',
            'onboarding_domisili' => false, 'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

        return User::where('email', 'onboard@example.test')->firstOrFail();
    };
    $this->approveResident = function () {
        $user = ($this->registerResident)();
        $this->actingAs($this->officer)->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka'])
            ->assertRedirect(route('verifikasi-warga.index'))->assertSessionHasNoErrors();

        return $user->fresh();
    };
});

test('register works with no houses and keeps pending state and ordinary role', function () {
    expect(Rumah::count())->toBe(0);
    $user = ($this->registerResident)();
    expect($user->rumah_id)->toBeNull()->and($user->rt_id)->toBeNull()
        ->and($user->onboarding_domisili)->toBeTrue()
        ->and($user->role_name)->toBe('warga')->and($user->status_akun)->toBe('pending_verifikasi')
        ->and($user->email_verified_at)->toBeNull()->and($user->warga->status_verifikasi)->toBe('pending');
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->actingAs($user)->get(route('domisili.create'))->assertRedirect(route('dashboard'));
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Belum Disetujui 1'])->assertForbidden();
    expect(Rumah::count())->toBe(0);
});

test('rt can review a new unassigned registration but cannot access legacy unassigned residents', function () {
    $user = ($this->registerResident)();
    $legacy = User::factory()->create(['role_id' => $user->role_id, 'rt_id' => null, 'status_akun' => 'pending_verifikasi']);
    $legacyWarga = Warga::create(['user_id' => $legacy->id, 'nama_lengkap' => 'Legacy tanpa RT', 'status_verifikasi' => 'pending']);
    $this->actingAs($this->officer)->get(route('verifikasi-warga.index'))->assertOk()->assertSee($user->name)->assertDontSee('Legacy tanpa RT');
    $this->get(route('verifikasi-warga.show', $user->warga))->assertOk()->assertSee('RT A');
    $this->get(route('verifikasi-warga.show', $legacyWarga))->assertNotFound();
    $this->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka', 'rt_id' => $this->rtOther->id])->assertSessionHasErrors('rt_id');
    expect($user->fresh()->status_akun)->toBe('pending_verifikasi');
    $this->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka'])->assertSessionHasNoErrors();
    expect($user->fresh()->status_akun)->toBe('aktif')->and($user->fresh()->rt_id)->toBe($this->rtOnboard->id)
        ->and($user->fresh()->rumah_id)->toBeNull()->and($user->fresh()->email_verified_at)->toBeNull();
    $otherOfficer = User::factory()->create(['role_id' => $this->officer->role_id, 'rt_id' => $this->rtOther->id]);
    $this->actingAs($otherOfficer)->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka'])->assertNotFound();
});

test('superadmin must explicitly choose an active rt before approving new registration', function () {
    $user = ($this->registerResident)();
    $this->actingAs($this->superadmin)->get(route('verifikasi-warga.show', $user->warga))->assertOk()->assertSee('RT warga setelah verifikasi');
    foreach ([null, 999999] as $rtId) {
        $this->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka', 'rt_id' => $rtId])->assertSessionHasErrors('rt_id');
    }
    $this->rtOther->update(['is_active' => false]);
    $this->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka', 'rt_id' => $this->rtOther->id])->assertSessionHasErrors('rt_id');
    $this->patch(route('verifikasi-warga.verifikasi', $user->warga), ['metode_verifikasi' => 'tatap_muka', 'rt_id' => $this->rtOnboard->id])->assertSessionHasNoErrors();
    expect($user->fresh()->rt_id)->toBe($this->rtOnboard->id)->and($user->fresh()->rumah_id)->toBeNull();
});

test('approved resident enters onboarding without email verification while regular pages remain gated', function () {
    $user = ($this->approveResident)();
    expect($user->email_verified_at)->toBeNull();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->get(route('verification.notice'))->assertRedirect(route('dashboard'));
    foreach (['kegiatan.index', 'direktori-rw.index', 'verifikasi-warga.index'] as $route) {
        $this->get(route($route))->assertRedirect(route('dashboard'));
    }
    $this->get(route('profile.edit'))->assertOk()->assertSee('Belum ada rumah di RT ini');
    $this->get(route('domisili.create'))->assertRedirect(route('dashboard'));
    $this->post(route('logout'))->assertRedirect('/');
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
});

test('first address is created and onboarding completed only after officer approval', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jl. Onboarding No. 7'])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
    expect($user->fresh()->rumah_id)->toBeNull()->and($user->fresh()->onboarding_domisili)->toBeTrue()
        ->and($user->fresh()->warga->status_domisili)->toBe('pending')->and(Rumah::count())->toBe(0);
    $this->actingAs($this->officer)->get(route('verifikasi-warga.index'))->assertOk()->assertSee('Jl. Onboarding No. 7');
    $this->get(route('verifikasi-warga.domisili.show', $user->warga))->assertOk()->assertSee('Jl. Onboarding No. 7');
    $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertRedirect(route('verifikasi-warga.index'))->assertSessionHasNoErrors();
    $user->refresh();
    $this->get(route('verifikasi-warga.index'))->assertOk()->assertSee('Langkah berikutnya: tetapkan PJ iuran')
        ->assertSee(route('admin.rumah.edit', $user->rumah_id), false);
    expect($user->onboarding_domisili)->toBeFalse()->and($user->rumah_id)->not->toBeNull()
        ->and($user->rt_id)->toBe($this->rtOnboard->id)->and($user->is_penanggung_jawab_rumah)->toBeFalse()
        ->and($user->rumah->alamat)->toBe('Jl. Onboarding No. 7')->and($user->rumah->penanggung_jawab_id)->toBeNull()
        ->and($user->warga->rumah_diajukan_id)->toBe($user->rumah_id)->and($user->warga->status_verifikasi)->toBe('terverifikasi')
        ->and($user->warga->status_domisili)->toBe('disetujui')->and($user->warga->domisili_diproses_oleh)->toBe($this->officer->id);
    $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertNotFound();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    $this->get(route('domisili.create'))->assertRedirect(route('dashboard'));
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Duplikat 2'])->assertForbidden();
    expect(Rumah::count())->toBe(1);
});

test('own rt house selection waits for approval and preserves its billing owner', function () {
    $user = ($this->approveResident)();
    $owner = User::factory()->create();
    $house = Rumah::create(['kode_rumah' => 'OWN-RT', 'alamat' => 'Jl. Terdaftar', 'rt_id' => $this->rtOnboard->id, 'penanggung_jawab_id' => $owner->id]);
    $other = Rumah::create(['kode_rumah' => 'OTHER-RT', 'alamat' => 'Alamat Privat RT B', 'rt_id' => $this->rtOther->id]);
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('OWN-RT')->assertDontSee('Alamat Privat RT B');
    $this->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $other->id])->assertNotFound();
    $this->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $house->id, 'alamat' => 'Alamat Tambahan'])->assertSessionHasErrors('alamat');
    $this->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $house->id])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
    expect($user->fresh()->rumah_id)->toBeNull()->and($user->fresh()->warga->rumah_diajukan_id)->toBe($house->id);
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    expect($user->fresh()->rumah_id)->toBe($house->id)->and($user->fresh()->is_penanggung_jawab_rumah)->toBeFalse()
        ->and($house->fresh()->penanggung_jawab_id)->toBe($owner->id)->and(Rumah::count())->toBe(2);
});

test('initial domicile rejects rt manipulation billing responsibility and inactive houses', function () {
    $user = ($this->approveResident)();
    $house = Rumah::create(['kode_rumah' => 'INACTIVE', 'rt_id' => $this->rtOnboard->id, 'status' => 'nonaktif']);
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $house->id])->assertNotFound();
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Baru 1', 'rt_id' => $this->rtOther->id, 'is_penanggung_jawab_rumah' => 1])->assertSessionHasErrors(['rt_id', 'is_penanggung_jawab_rumah']);
    expect($user->fresh()->rumah_id)->toBeNull()->and($user->fresh()->onboarding_domisili)->toBeTrue();
});

test('initial domicile reuses identical active address without duplicate house', function () {
    $user = ($this->approveResident)();
    $house = Rumah::create(['kode_rumah' => 'EXISTING', 'alamat' => 'Jalan Sama 10', 'rt_id' => $this->rtOnboard->id]);
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => ' jalan sama 10 '])->assertRedirect(route('profile.edit'));
    expect($user->fresh()->rumah_id)->toBeNull();
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    expect(Rumah::count())->toBe(1)->and($user->fresh()->rumah_id)->toBe($house->id);
});

test('legacy accounts cannot use new onboarding to bypass domicile restrictions', function () {
    $legacy = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'), 'rt_id' => $this->rtOnboard->id]);
    Warga::create(['user_id' => $legacy->id, 'nama_lengkap' => $legacy->name, 'status_verifikasi' => 'terverifikasi']);
    $this->actingAs($legacy)->get(route('domisili.create'))->assertRedirect(route('dashboard'));
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Bypass 1'])->assertForbidden();
});

test('manual domicile assignment by officer closes initial setup entitlement', function () {
    $user = ($this->approveResident)();
    $house = Rumah::create(['kode_rumah' => 'ASSIGNED', 'rt_id' => $this->rtOnboard->id]);
    $user->update(['rumah_id' => $house->id]);
    expect($user->fresh()->onboarding_domisili)->toBeFalse();
    $this->actingAs($user->fresh())->get(route('domisili.create'))->assertRedirect(route('dashboard'));
});

test('new unassigned registration can be rejected without granting initial domicile access', function () {
    $user = ($this->registerResident)();
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.tolak', $user->warga), ['catatan_verifikasi' => 'Identitas belum dapat dikonfirmasi.'])->assertRedirect(route('verifikasi-warga.index'));
    expect($user->fresh()->status_akun)->toBe('ditolak')->and($user->fresh()->rt_id)->toBeNull();
    $this->actingAs($user->fresh())->get(route('domisili.create'))->assertRedirect(route('dashboard'));
});

test('inactive rt cannot review unassigned registrations or receive a new domicile', function () {
    $user = ($this->approveResident)();
    $this->rtOnboard->update(['is_active' => false]);
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Nonaktif 8'])->assertNotFound();
    $user->update(['status_akun' => 'pending_verifikasi', 'rt_id' => null]);
    $user->warga->update(['status_verifikasi' => 'pending']);
    $this->actingAs($this->officer)->get(route('verifikasi-warga.show', $user->warga))->assertNotFound();
});

test('proposed house never grants payment proof access before approval', function (string $mode) {
    Storage::fake('local');
    $house = Rumah::create(['kode_rumah' => 'PRIVATE-HOUSE', 'alamat' => 'Jalan Privat Nomor 10', 'rt_id' => $this->rtOnboard->id, 'status' => 'aktif']);
    $owner = User::factory()->create(['rt_id' => $house->rt_id, 'rumah_id' => $house->id, 'is_penanggung_jawab_rumah' => true]);
    $house->update(['penanggung_jawab_id' => $owner->id]);
    $bill = Tagihan::create([
        'user_id' => $owner->id, 'rumah_id' => $house->id, 'rt_id' => $house->rt_id,
        'bulan' => 10, 'tahun' => 2026, 'total' => 50000, 'status' => 'lunas',
        'judul' => 'PRIVATE HOUSEHOLD BILL', 'bukti' => 'tagihan-bukti/private-house.txt',
    ]);
    Storage::disk('local')->put($bill->bukti, 'private proof');
    $user = ($this->approveResident)();
    $payload = $mode === 'existing' ? ['mode' => $mode, 'rumah_id' => $house->id] : ['mode' => $mode, 'alamat' => ' jalan privat nomor 10 '];
    $this->actingAs($user)->post(route('domisili.store'), $payload + [
        'onboarding_domisili' => false, 'status_domisili' => 'disetujui',
        'domisili_diproses_oleh' => $this->officer->id,
    ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
    $user->refresh();
    expect($user->rumah_id)->toBeNull()->and($user->onboarding_domisili)->toBeTrue()
        ->and($user->warga->rumah_diajukan_id)->toBe($house->id)
        ->and($user->warga->rumah_aktual)->toBeNull()
        ->and($user->warga->status_domisili)->toBe('pending')->and($user->warga->domisili_diproses_oleh)->toBeNull();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Menunggu persetujuan domisili')
        ->assertDontSee('name="rumah_id"', false);
    $this->get(route('tagihan.bukti', $bill))->assertRedirect(route('dashboard'));
    // Check the data authorization too, independently of the onboarding redirect.
    $this->withoutMiddleware(EnsureInitialDomicile::class)->get(route('tagihan.bukti', $bill))->assertForbidden();
    $this->get(route('tagihan.index'))->assertOk()->assertDontSee('PRIVATE HOUSEHOLD BILL');
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    $this->actingAs($user->fresh())->get(route('tagihan.bukti', $bill))->assertOk();
    expect($house->fresh()->penanggung_jawab_id)->toBe($owner->id)->and($user->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['event' => 'domisili_disetujui', 'user_id' => $this->officer->id, 'auditable_id' => $user->warga->id]);
})->with(['existing', 'new']);

test('pending domicile cannot be replaced or self approved', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Pertama Nomor 1'])->assertSessionHasNoErrors();
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Pengganti Nomor 2'])->assertSessionHasErrors('domisili');
    $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertForbidden();
    expect($user->fresh()->warga->alamat_domisili)->toBe('Alamat Pertama Nomor 1')
        ->and($user->fresh()->warga->status_domisili)->toBe('pending')->and($user->fresh()->rumah_id)->toBeNull();
    $this->withoutMiddleware(EnsureInitialDomicile::class)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertForbidden();
});

test('officer can return only domicile for correction and resident can resubmit', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Belum Benar 1'])->assertSessionHasNoErrors();
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.perbaiki', $user->warga), ['catatan_domisili' => 'pendek'])->assertSessionHasErrors('catatan_domisili');
    $this->patch(route('verifikasi-warga.domisili.perbaiki', $user->warga), ['catatan_domisili' => 'Mohon lengkapi nomor rumah yang benar.'])->assertSessionHasNoErrors();
    $user->refresh();
    expect($user->status_akun)->toBe('aktif')->and($user->warga->status_verifikasi)->toBe('terverifikasi')
        ->and($user->warga->status_domisili)->toBe('perlu_perbaikan')->and($user->rumah_id)->toBeNull();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Mohon lengkapi nomor rumah yang benar.');
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Benar Nomor 23'])->assertSessionHasNoErrors();
    expect($user->fresh()->warga->status_domisili)->toBe('pending')->and($user->fresh()->warga->catatan_domisili)->toBeNull();
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    expect($user->fresh()->rumah->alamat)->toBe('Jalan Benar Nomor 23');
    $this->assertDatabaseHas('audit_logs', ['event' => 'domisili_perlu_perbaikan', 'user_id' => $this->officer->id]);
});

test('domicile review is restricted to the assigned rt or rw and global operator', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Rahasia Pengajuan 1'])->assertSessionHasNoErrors();
    $otherOfficer = User::factory()->create(['role_id' => $this->officer->role_id, 'rt_id' => $this->rtOther->id]);
    $otherRw = Rw::create(['name' => 'RW Lain', 'is_active' => true]);
    $otherRwRt = Rt::create(['name' => 'RT RW Lain', 'rw_id' => $otherRw->id, 'is_active' => true]);
    $otherRwOfficer = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rw')->value('id'), 'rt_id' => $otherRwRt->id]);
    foreach ([$otherOfficer, $otherRwOfficer] as $actor) {
        $this->actingAs($actor)->get(route('verifikasi-warga.index'))->assertOk()->assertDontSee('Alamat Rahasia Pengajuan 1');
        $this->get(route('verifikasi-warga.domisili.show', $user->warga))->assertNotFound();
        $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertNotFound();
        $this->patch(route('verifikasi-warga.domisili.perbaiki', $user->warga), ['catatan_domisili' => 'Tidak boleh diubah oleh wilayah lain.'])->assertNotFound();
    }
    $rwOfficer = User::factory()->create(['role_id' => $otherRwOfficer->role_id, 'rt_id' => $this->rtOnboard->id]);
    $this->actingAs($rwOfficer)->get(route('verifikasi-warga.domisili.show', $user->warga))->assertOk();
    $this->actingAs($this->superadmin)->get(route('verifikasi-warga.index'))->assertSee('Alamat Rahasia Pengajuan 1');
    $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    expect($user->fresh()->warga->domisili_diproses_oleh)->toBe($this->superadmin->id);
});

test('house must still be active in the approved rt when domicile is accepted', function (string $change) {
    $user = ($this->approveResident)();
    $house = Rumah::create(['kode_rumah' => 'CHANGED-HOUSE', 'alamat' => 'Alamat Semula Nomor 1', 'rt_id' => $this->rtOnboard->id, 'status' => 'aktif']);
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $house->id])->assertSessionHasNoErrors();
    if ($change === 'deleted') {
        $house->delete();
    } else {
        $house->update($change === 'inactive' ? ['status' => 'nonaktif'] : ['rt_id' => $this->rtOther->id]);
    }
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasErrors('domisili');
    expect($user->fresh()->rumah_id)->toBeNull()->and($user->fresh()->onboarding_domisili)->toBeTrue()
        ->and($user->fresh()->warga->status_domisili)->toBe('pending');
})->with(['inactive', 'moved', 'deleted']);

test('review never accepts a different house supplied in the approval request', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat yang Diperiksa 99'])->assertSessionHasNoErrors();
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga), ['rumah_id' => 999, 'rt_id' => $this->rtOther->id])
        ->assertSessionHasErrors(['rumah_id', 'rt_id']);
    expect($user->fresh()->rumah_id)->toBeNull()->and(Rumah::count())->toBe(0);
});

test('address becoming an existing household requires renewed proposal before access can be granted', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Jalan Bersama Nomor 10'])->assertSessionHasNoErrors();
    $house = Rumah::create(['kode_rumah' => 'LATE-HOUSE', 'alamat' => 'Jalan Bersama Nomor 10', 'rt_id' => $this->rtOnboard->id, 'status' => 'aktif']);
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasErrors('domisili');
    expect($user->fresh()->rumah_id)->toBeNull()->and(Rumah::count())->toBe(1);
    $this->patch(route('verifikasi-warga.domisili.perbaiki', $user->warga), ['catatan_domisili' => 'Alamat sudah tersedia. Pilih rumah yang terdaftar.'])->assertSessionHasNoErrors();
    $this->actingAs($user->fresh())->post(route('domisili.store'), ['mode' => 'existing', 'rumah_id' => $house->id])->assertSessionHasNoErrors();
    $this->actingAs($this->officer)->get(route('verifikasi-warga.domisili.show', $user->warga))->assertSee('LATE-HOUSE');
    $this->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertSessionHasNoErrors();
    expect(Rumah::count())->toBe(1)->and($user->fresh()->rumah_id)->toBe($house->id);
});

test('manual assignment after a proposal prevents stale review from changing the house', function () {
    $user = ($this->approveResident)();
    $this->actingAs($user)->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat Pengajuan Lama 10'])->assertSessionHasNoErrors();
    $house = Rumah::create(['kode_rumah' => 'MANUAL-HOUSE', 'rt_id' => $this->rtOnboard->id, 'status' => 'aktif']);
    $user->update(['rumah_id' => $house->id]);
    $this->actingAs($this->officer)->patch(route('verifikasi-warga.domisili.setujui', $user->warga))->assertNotFound();
    expect($user->fresh()->rumah_id)->toBe($house->id)->and(Rumah::count())->toBe(1);
});
