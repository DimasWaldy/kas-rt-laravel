<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Notification::fake();
    $rw = Rw::create(['name' => 'RW Recovery', 'is_active' => true]);
    $this->rtRecovery = Rt::create(['name' => 'RT Recovery', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->reviewer = User::factory()->create([
        'role_id' => Role::where('name', 'ketua_rt')->value('id'), 'rt_id' => $this->rtRecovery->id,
    ]);
    $this->applicant = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id'), 'status_akun' => 'ditolak',
        'phone' => '081234567890', 'onboarding_domisili' => true,
    ]);
    Warga::create([
        'user_id' => $this->applicant->id, 'nama_lengkap' => $this->applicant->name,
        'status_verifikasi' => 'ditolak', 'catatan_verifikasi' => 'Nama tidak sesuai identitas. Mohon diperbaiki.',
        'diverifikasi_oleh' => $this->reviewer->id, 'diverifikasi_at' => now(),
    ]);
    $this->correction = ['name' => 'Nama Sesuai Identitas', 'phone' => '081298765432',
        'klarifikasi' => 'Nama sudah sesuai KTP. Saya siap verifikasi tatap muka.'];
});

test('rejected account signs in to private status instead of intended services and can logout', function () {
    $other = User::factory()->create();
    Warga::create(['user_id' => $other->id, 'nama_lengkap' => $other->name, 'catatan_verifikasi' => 'Catatan rahasia orang lain']);
    $this->withSession(['url.intended' => route('tagihan.index')])->post(route('login'), [
        'email' => $this->applicant->email, 'password' => 'password', 'remember' => true,
    ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors()->assertSessionMissing('url.intended');
    $this->assertAuthenticatedAs($this->applicant);
    $this->get(route('pendaftaran.show', ['user_id' => $other->id]))->assertOk()
        ->assertSee('Nama tidak sesuai identitas. Mohon diperbaiki.')->assertSee('Kirim perbaikan')
        ->assertDontSee('Catatan rahasia orang lain')->assertDontSee('name="email"', false)
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
});

test('wrong credentials never reveal rejection reason and login remains rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login'), ['email' => $this->applicant->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email')->assertDontSee('Nama tidak sesuai identitas');
    }
    $this->post(route('login'), ['email' => $this->applicant->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
    RateLimiter::clear(strtolower($this->applicant->email).'|127.0.0.1');
});

test('pending and rejected sessions cannot read or mutate resident services even for legacy accounts', function (string $status, bool $onboarding) {
    Storage::fake('local');
    $bill = Tagihan::create(['user_id' => $this->applicant->id, 'bulan' => 10, 'tahun' => 2026,
        'total' => 50000, 'status' => 'lunas', 'judul' => 'PRIVATE BILL', 'bukti' => 'tagihan-bukti/recovery.txt']);
    Storage::disk('local')->put($bill->bukti, 'private payment proof');
    $this->applicant->update(['status_akun' => $status, 'onboarding_domisili' => $onboarding]);
    $this->applicant->warga->update(['status_verifikasi' => $status === 'ditolak' ? 'ditolak' : 'pending']);
    $this->actingAs($this->applicant->fresh());
    foreach (['tagihan.index', 'domisili.create', 'verifikasi-warga.index', 'direktori-rw.index'] as $name) {
        $this->get(route($name))->assertRedirect(route('dashboard'));
        $this->getJson(route($name))->assertForbidden();
    }
    $this->get(route('dashboard'))->assertOk();
    if ($status === 'pending_verifikasi') {
        $this->get(route('profile.edit'))->assertOk()->assertSee('Data Domisili');
        $this->patch(route('profile.update'), ['name' => 'Profil Lengkap', 'email' => $this->applicant->email])
            ->assertRedirect(route('profile.edit'));
    } else {
        $this->get(route('profile.edit'))->assertRedirect(route('dashboard'));
        $this->getJson(route('profile.edit'))->assertForbidden();
        $this->patch(route('profile.update'), ['name' => 'Bypass', 'email' => $this->applicant->email])->assertForbidden();
    }
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertForbidden();
    $this->post(route('domisili.store'), ['mode' => 'new', 'alamat' => 'Alamat bypass'])->assertForbidden();
    $this->get(route('tagihan.bukti', $bill))->assertRedirect(route('dashboard'));
    $this->get(route('pendaftaran.show'))->assertOk();
    expect($this->applicant->fresh()->name)->not->toBe('Bypass')->and(Rumah::count())->toBe(0);
})->with([['ditolak', true], ['pending_verifikasi', true], ['ditolak', false], ['pending_verifikasi', false]]);

test('rejected applicant resubmits same account and returns to officer queue with review history', function () {
    $user = $this->applicant;
    $before = User::count();
    $this->actingAs($user)->post(route('pendaftaran.resubmit'), $this->correction)
        ->assertRedirect(route('pendaftaran.show'))->assertSessionHasNoErrors();
    $fresh = $user->fresh();
    expect(User::count())->toBe($before)->and(Warga::where('user_id', $user->id)->count())->toBe(1)
        ->and($fresh->email)->toBe($user->email)->and($fresh->password)->toBe($user->password)
        ->and($fresh->name)->toBe($this->correction['name'])->and($fresh->phone)->toBe($this->correction['phone'])
        ->and($fresh->status_akun)->toBe('pending_verifikasi')->and($fresh->rt_id)->toBeNull()
        ->and($fresh->rumah_id)->toBeNull()->and($fresh->onboarding_domisili)->toBeTrue()
        ->and($fresh->warga->status_verifikasi)->toBe('pending')->and($fresh->warga->diverifikasi_at)->toBeNull()
        ->and($fresh->warga->diverifikasi_oleh)->toBeNull()->and($fresh->warga->catatan_verifikasi)->toBeNull();
    $audit = AuditLog::where('event', 'pendaftaran_diajukan_ulang')->sole();
    expect($audit->old_values['catatan_verifikasi'])->toBe('Nama tidak sesuai identitas. Mohon diperbaiki.')
        ->and($audit->notes)->toBe($this->correction['klarifikasi'])->and($audit->user_id)->toBe($user->id);
    $this->actingAs($fresh)->get(route('pendaftaran.show'))->assertOk()->assertSee('Menunggu verifikasi pengurus')
        ->assertDontSee('name="klarifikasi"', false);
    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Pendaftaran Anda sedang diperiksa');
    $this->actingAs($this->reviewer)->get(route('verifikasi-warga.index'))->assertOk()->assertSee($fresh->name);
    $this->get(route('verifikasi-warga.show', $fresh->warga))->assertOk()->assertSee('Pengajuan ulang setelah perbaikan')
        ->assertSee($this->correction['klarifikasi'])->assertSee('Nama tidak sesuai identitas. Mohon diperbaiki.');
    $this->patch(route('verifikasi-warga.verifikasi', $fresh->warga), ['metode_verifikasi' => 'tatap_muka'])
        ->assertSessionHasNoErrors();
    $this->actingAs($fresh->fresh())->get(route('pendaftaran.show'))->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk();
    $this->get(route('profile.edit'))->assertOk()->assertSee('Data Domisili');
    expect($fresh->fresh()->rt_id)->toBe($this->rtRecovery->id)->and($fresh->fresh()->rumah_id)->toBeNull();
});

test('duplicate or stale correction cannot reset pending or approved status', function () {
    $this->actingAs($this->applicant)->post(route('pendaftaran.resubmit'), $this->correction)->assertSessionHasNoErrors();
    // Keep the stale rejected model to ensure the transaction reloads the authoritative state.
    $this->post(route('pendaftaran.resubmit'), array_merge($this->correction, ['name' => 'Duplicate']))
        ->assertSessionHasErrors('pendaftaran');
    $this->applicant->fresh()->update(['status_akun' => 'aktif']);
    $this->applicant->warga->update(['status_verifikasi' => 'terverifikasi']);
    $this->post(route('pendaftaran.resubmit'), $this->correction)->assertSessionHasErrors('pendaftaran');
    expect(AuditLog::where('event', 'pendaftaran_diajukan_ulang')->count())->toBe(1)
        ->and($this->applicant->fresh()->status_akun)->toBe('aktif')
        ->and($this->applicant->fresh()->name)->toBe($this->correction['name']);
});

test('correction validates data and cannot assign identity ownership or approval fields', function () {
    $this->actingAs($this->applicant)->post(route('pendaftaran.resubmit'), ['name' => '', 'phone' => 'abc', 'klarifikasi' => 'x'])
        ->assertSessionHasErrors(['name', 'phone', 'klarifikasi']);
    $forbidden = ['user_id' => $this->reviewer->id, 'email' => 'changed@example.test', 'role_id' => $this->reviewer->role_id,
        'status_akun' => 'aktif', 'status_verifikasi' => 'terverifikasi', 'rt_id' => $this->rtRecovery->id,
        'rumah_id' => 1, 'kartu_keluarga_id' => 1, 'is_penanggung_jawab_rumah' => true];
    $this->post(route('pendaftaran.resubmit'), array_merge($this->correction, $forbidden))->assertSessionHasErrors(array_keys($forbidden));
    expect($this->applicant->fresh()->status_akun)->toBe('ditolak')->and(AuditLog::count())->toBe(0);
});

test('guest cannot inspect or resubmit and duplicate registration gives recovery instructions without overwriting', function () {
    $this->get(route('pendaftaran.show'))->assertRedirect(route('login'));
    $this->post(route('pendaftaran.resubmit'), $this->correction)->assertRedirect(route('login'));
    $this->post(route('register'), array_merge($this->correction, [
        'email' => $this->applicant->email, 'password' => 'new-password', 'password_confirmation' => 'new-password',
    ]))->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Masuk dengan akun yang sama untuk melihat status atau memperbaiki pendaftaran. Jika lupa password, gunakan Lupa Password.']);
    expect($this->applicant->fresh()->password)->toBe($this->applicant->password)
        ->and($this->applicant->fresh()->status_akun)->toBe('ditolak');
});

test('legacy scoped reapplication preserves territory and is invisible to other rt officers', function () {
    $this->applicant->update(['rt_id' => $this->rtRecovery->id, 'onboarding_domisili' => false]);
    $this->actingAs($this->applicant)->post(route('pendaftaran.resubmit'), $this->correction)->assertSessionHasNoErrors();
    $otherRt = Rt::create(['name' => 'RT Other', 'rw_id' => $this->rtRecovery->rw_id, 'is_active' => true]);
    $otherOfficer = User::factory()->create(['role_id' => $this->reviewer->role_id, 'rt_id' => $otherRt->id]);
    $this->actingAs($otherOfficer)->get(route('verifikasi-warga.show', $this->applicant->warga))->assertNotFound();
    $this->get(route('verifikasi-warga.index'))->assertDontSee($this->correction['name']);
    $this->actingAs($this->reviewer)->get(route('verifikasi-warga.show', $this->applicant->warga))->assertOk();
    expect($this->applicant->fresh()->rt_id)->toBe($this->rtRecovery->id);
});

test('repeated rejections preserve each reason and show latest reason and clarification safely', function () {
    $this->actingAs($this->applicant)->post(route('pendaftaran.resubmit'), $this->correction)->assertSessionHasNoErrors();
    $reason = '<script>alert("reason")</script> Nomor HP perlu dikoreksi.';
    $this->actingAs($this->reviewer)->patch(route('verifikasi-warga.tolak', $this->applicant->warga), ['catatan_verifikasi' => $reason])
        ->assertSessionHasNoErrors();
    $this->actingAs($this->applicant->fresh())->get(route('pendaftaran.show'))->assertSee($reason)->assertDontSee('<script>alert', false);
    $note = '<script>alert("note")</script> Nomor HP sudah dikoreksi.';
    $this->post(route('pendaftaran.resubmit'), array_merge($this->correction, ['klarifikasi' => $note]))->assertSessionHasNoErrors();
    $this->actingAs($this->reviewer)->get(route('verifikasi-warga.show', $this->applicant->warga))
        ->assertSee($reason)->assertSee($note)->assertDontSee('<script>alert', false);
    expect(AuditLog::where('event', 'pendaftaran_diajukan_ulang')->count())->toBe(2)
        ->and(AuditLog::where('event', 'pendaftaran_ditolak')->sole()->notes)->toBe($reason);
});

test('rejected account can recover a forgotten password without gaining approved access', function () {
    $this->post(route('password.email'), ['email' => $this->applicant->email])->assertSessionHasNoErrors();
    Notification::assertSentTo($this->applicant, ResetPassword::class, function ($notification) {
        $this->post(route('password.store'), ['token' => $notification->token, 'email' => $this->applicant->email,
            'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors();

        return true;
    });
    $this->post(route('login'), ['email' => $this->applicant->email, 'password' => 'new-password'])
        ->assertRedirect(route('dashboard'));
    expect($this->applicant->fresh()->status_akun)->toBe('ditolak');
});

test('active account and inconsistent rejected records cannot use correction to reset verification', function () {
    $this->actingAs($this->reviewer)->get(route('pendaftaran.show'))->assertRedirect(route('dashboard'));
    $this->applicant->warga->update(['status_verifikasi' => 'terverifikasi']);
    $this->actingAs($this->applicant)->get(route('pendaftaran.show'))->assertOk()->assertDontSee('name="klarifikasi"', false);
    $this->post(route('pendaftaran.resubmit'), $this->correction)->assertSessionHasErrors('pendaftaran');
    expect($this->applicant->fresh()->warga->status_verifikasi)->toBe('terverifikasi');
});
