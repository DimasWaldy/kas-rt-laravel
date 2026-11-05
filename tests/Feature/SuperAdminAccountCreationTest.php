<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Notification::fake();

    $this->rw = Rw::create(['name' => 'RW Akun', 'is_active' => true]);
    $this->rt = Rt::create(['rw_id' => $this->rw->id, 'name' => 'RT Akun', 'is_active' => true]);
    $this->superAdmin = User::factory()->create([
        'role_id' => Role::where('name', 'super_admin')->value('id'),
    ]);
});

test('only super admin can open or submit account creation', function () {
    $payload = ['name' => 'Pengurus Baru', 'email' => 'pengurus@example.test', 'role' => 'ketua_rt', 'rt_id' => $this->rt->id];

    $this->get(route('admin.accounts.create'))->assertRedirect(route('login'));
    $this->post(route('admin.accounts.store'), $payload)->assertRedirect(route('login'));

    foreach (['admin', 'ketua_rt', 'warga'] as $role) {
        $user = User::factory()->create(['role_id' => Role::where('name', $role)->value('id'), 'rt_id' => $this->rt->id]);
        if ($role === 'admin') {
            $this->actingAs($user)->get(route('admin.dashboard'))
                ->assertOk()->assertDontSee(route('admin.accounts.create'));
        }
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('admin.accounts.create'))
            ->assertDontSee('Manajemen Akun');
        $this->actingAs($user)->get(route('admin.accounts.create'))->assertForbidden();
        $this->post(route('admin.accounts.store'), $payload)->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    $this->actingAs($this->superAdmin)->get(route('dashboard'))
        ->assertOk()->assertSee('Manajemen Akun')->assertSee(route('admin.accounts.create'));
    $this->get(route('admin.dashboard'))
        ->assertOk()->assertSee(route('admin.accounts.create'));

    $this->superAdmin->role->permissions()->detach(
        $this->superAdmin->role->permissions()->where('name', 'admin-only')->firstOrFail()->id
    );
    $this->superAdmin->unsetRelation('role');
    $this->get(route('admin.accounts.create'))->assertForbidden();
    $this->post(route('admin.accounts.store'), $payload)->assertForbidden();
    $this->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.accounts.create'));
});

test('super admin can create admin without RT and send password setup link', function () {
    $this->actingAs($this->superAdmin)->get(route('admin.accounts.create'))
        ->assertOk()->assertSee('Buat akun warga')
        ->assertSee('Peran dan cakupan wilayah')->assertSee('Aktivasi akun')
        ->assertSee('ketua_rw')->assertSee('bendahara_rt')->assertDontSee('value="super_admin"', false);

    $this->post(route('admin.accounts.store'), [
        'name' => 'Admin Baru', 'email' => 'admin-baru@example.test', 'role' => 'admin',
    ])->assertRedirect(route('admin.accounts.create'))->assertSessionHas('success');

    $user = User::where('email', 'admin-baru@example.test')->firstOrFail();
    expect($user->role_name)->toBe('admin')
        ->and($user->rt_id)->toBeNull()
        ->and($user->status_akun)->toBe('aktif')
        ->and(Hash::check('password', $user->password))->toBeFalse();
    Notification::assertSentTo($user, ResetPassword::class);
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->superAdmin->id, 'auditable_type' => User::class,
        'auditable_id' => $user->id, 'event' => 'akun_dibuat',
    ]);
    expect(AuditLog::where('auditable_id', $user->id)->where('auditable_type', User::class)
        ->firstOrFail()->new_values)->toBe(['role' => 'admin', 'rt_id' => null]);
});

test('super admin can use existing resident form with household data', function () {
    $this->actingAs($this->superAdmin)->get(route('admin.warga.create'))
        ->assertOk()->assertSee('Data Rumah / Unit Hunian');

    $this->post(route('admin.warga.store'), [
        'name' => 'Warga Baru', 'email' => 'warga-baru@example.test',
        'password' => 'password-aman-123', 'rt_id' => $this->rt->id,
        'is_kepala_keluarga' => true,
    ])->assertRedirect(route('admin.warga.index'));

    $user = User::where('email', 'warga-baru@example.test')->firstOrFail();
    expect($user->role_name)->toBe('warga')
        ->and($user->rt_id)->toBe($this->rt->id)
        ->and($user->warga?->status_dalam_kk)->toBe('kepala_keluarga');
});

test('RT and RW staff receive RT placement and RW scope', function () {
    $this->actingAs($this->superAdmin);

    foreach (['ketua_rt', 'bendahara_rt', 'sekretaris', 'ketua_rw', 'bendahara_rw', 'petugas_posyandu'] as $role) {
        $email = $role.'@example.test';
        $this->post(route('admin.accounts.store'), [
            'name' => 'Pengurus '.$role, 'email' => $email, 'role' => $role, 'rt_id' => $this->rt->id,
        ])->assertRedirect(route('admin.accounts.create'))->assertSessionHas('success');

        $user = User::where('email', $email)->firstOrFail();
        expect($user->role_name)->toBe($role)
            ->and($user->rt_id)->toBe($this->rt->id);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    expect(User::where('email', 'ketua_rw@example.test')->firstOrFail()->rt->rw_id)->toBe($this->rw->id);
});

test('super admin cannot create super admin, resident, or account in inactive territory', function () {
    $this->actingAs($this->superAdmin);
    $payload = ['name' => 'Akun Salah', 'email' => 'salah@example.test', 'role' => 'ketua_rt', 'rt_id' => $this->rt->id];

    foreach (['super_admin', 'warga', 'role_palsu'] as $role) {
        $this->post(route('admin.accounts.store'), [...$payload, 'role' => $role])
            ->assertSessionHasErrors('role');
    }

    $this->post(route('admin.accounts.store'), [...$payload, 'role' => 'admin'])
        ->assertSessionHasErrors('rt_id');
    $this->post(route('admin.accounts.store'), [...$payload, 'rt_id' => null])
        ->assertSessionHasErrors('rt_id');

    $this->rt->update(['is_active' => false]);
    $this->post(route('admin.accounts.store'), $payload)->assertSessionHasErrors('rt_id');
    $this->rt->update(['is_active' => true]);
    $this->rw->update(['is_active' => false]);
    $this->post(route('admin.accounts.store'), $payload)->assertSessionHasErrors('rt_id');

    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    Notification::assertNothingSent();
});

test('duplicate email and invalid role placement are rejected', function () {
    $this->actingAs($this->superAdmin);
    $existing = User::factory()->create();

    $this->post(route('admin.accounts.store'), [
        'name' => 'Duplikat', 'email' => $existing->email, 'role' => 'admin',
    ])->assertSessionHasErrors('email');

    $this->post(route('admin.accounts.store'), [
        'name' => 'Wilayah Salah', 'email' => 'wilayah-salah@example.test',
        'role' => 'ketua_rw', 'rt_id' => 999999,
    ])->assertSessionHasErrors('rt_id');
});
