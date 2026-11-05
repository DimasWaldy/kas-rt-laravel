<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
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
    $this->payload = [
        'name' => 'Pengurus Baru', 'email' => 'pengurus@example.test',
        'password' => 'password-aman-123', 'password_confirmation' => 'password-aman-123',
        'role' => 'ketua_rt', 'rt_id' => $this->rt->id,
    ];
});

test('admin and super admin can access account creation but other roles cannot', function () {
    $this->get(route('admin.accounts.create'))->assertRedirect(route('login'));
    $this->post(route('admin.accounts.store'), $this->payload)->assertRedirect(route('login'));

    foreach (['ketua_rt', 'warga'] as $role) {
        $user = User::factory()->create(['role_id' => Role::where('name', $role)->value('id'), 'rt_id' => $this->rt->id]);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.accounts.create'));
        $this->get(route('admin.accounts.create'))->assertForbidden();
        $this->post(route('admin.accounts.store'), $this->payload)->assertForbidden();
    }

    foreach (['admin', 'super_admin'] as $role) {
        $user = $role === 'admin'
            ? User::factory()->create(['role_id' => Role::where('name', $role)->value('id')])
            : $this->superAdmin;
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Manajemen Akun')->assertSee(route('admin.accounts.create'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.accounts.create'));
        $this->get(route('admin.accounts.create'))->assertOk();
    }

    $this->superAdmin->role->permissions()->detach(
        $this->superAdmin->role->permissions()->where('name', 'admin-only')->firstOrFail()->id
    );
    $this->superAdmin->unsetRelation('role');
    $this->get(route('admin.accounts.create'))->assertForbidden();
    $this->post(route('admin.accounts.store'), $this->payload)->assertForbidden();
    $this->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.accounts.create'));
    $this->assertDatabaseMissing('users', ['email' => $this->payload['email']]);
});

test('admin can create operator with initial password without sending reset link or exposing password in audit', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $this->actingAs($admin)->get(route('admin.accounts.create'))
        ->assertOk()->assertSee('Buat akun warga')->assertSee('Peran dan cakupan wilayah')
        ->assertSee('Password awal')->assertSee('ketua_rw')->assertSee('bendahara_rt')
        ->assertDontSee('value="bendahara"', false)->assertDontSee('value="sekretaris"', false)
        ->assertDontSee('value="super_admin"', false);

    $this->post(route('admin.accounts.store'), [
        ...$this->payload, 'name' => 'Admin Baru', 'email' => 'admin-baru@example.test',
        'role' => 'admin', 'rt_id' => null,
    ])->assertRedirect(route('admin.accounts.create'))->assertSessionHas('success');

    $user = User::where('email', 'admin-baru@example.test')->firstOrFail();
    expect($user->role_name)->toBe('admin')
        ->and($user->rt_id)->toBeNull()
        ->and($user->rw_id)->toBeNull()
        ->and($user->status_akun)->toBe('aktif')
        ->and(Hash::check($this->payload['password'], $user->password))->toBeTrue();
    Notification::assertNothingSent();
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id, 'auditable_type' => User::class,
        'auditable_id' => $user->id, 'event' => 'akun_dibuat',
    ]);
    expect(AuditLog::where('auditable_id', $user->id)->where('auditable_type', User::class)
        ->firstOrFail()->new_values)->toBe(['role' => 'admin', 'rt_id' => null, 'rw_id' => null]);
});

test('resident creation remains in resident flow and creates resident and household records', function () {
    $this->actingAs($this->superAdmin)->get(route('admin.accounts.create'))
        ->assertOk()->assertSee(route('admin.warga.create'));
    $this->get(route('admin.warga.create'))->assertOk()->assertSee('Data Rumah / Unit Hunian');

    $this->post(route('admin.warga.store'), [
        'name' => 'Warga Baru', 'email' => 'warga-baru@example.test',
        'password' => 'password-aman-123', 'rt_id' => $this->rt->id,
        'rumah_kode' => 'RUMAH-AKUN-001', 'no_kk' => '1234567890123456',
        'is_kepala_keluarga' => true,
    ])->assertRedirect(route('admin.warga.index'));

    $user = User::where('email', 'warga-baru@example.test')->firstOrFail();
    expect($user->role_name)->toBe('warga')
        ->and($user->rt_id)->toBe($this->rt->id)
        ->and($user->warga?->status_dalam_kk)->toBe('kepala_keluarga')
        ->and($user->warga?->kartuKeluarga?->no_kk)->toBe('1234567890123456')
        ->and($user->rumah?->kode_rumah)->toBe('RUMAH-AKUN-001');
});

test('RT staff use RT and RW staff use RW without representative RT', function () {
    $this->actingAs($this->superAdmin);

    foreach (['ketua_rt', 'bendahara_rt', 'sekretaris_rt'] as $role) {
        $email = $role.'@example.test';
        $this->post(route('admin.accounts.store'), [...$this->payload, 'email' => $email, 'role' => $role])
            ->assertRedirect(route('admin.accounts.create'))->assertSessionHas('success');
        $user = User::where('email', $email)->firstOrFail();
        expect($user->role_name)->toBe($role)
            ->and($user->rt_id)->toBe($this->rt->id)
            ->and($user->rw_id)->toBeNull();
    }

    foreach (['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'] as $role) {
        $email = $role.'@example.test';
        $this->post(route('admin.accounts.store'), [
            ...$this->payload, 'email' => $email, 'role' => $role, 'rt_id' => null, 'rw_id' => $this->rw->id,
        ])->assertRedirect(route('admin.accounts.create'))->assertSessionHas('success');
        $user = User::where('email', $email)->firstOrFail();
        expect($user->role_name)->toBe($role)
            ->and($user->rt_id)->toBeNull()
            ->and($user->rw_id)->toBe($this->rw->id)
            ->and($user->assignedRwId())->toBe($this->rw->id);
    }
    Notification::assertNothingSent();
});

test('admin cannot create super admin, resident, legacy RT roles or inactive territory account', function () {
    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $this->actingAs($admin);

    foreach (['super_admin', 'warga', 'bendahara', 'sekretaris', 'role_palsu'] as $role) {
        $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => $role])
            ->assertSessionHasErrors('role');
    }
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'admin'])
        ->assertSessionHasErrors('rt_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'rt_id' => null])
        ->assertSessionHasErrors('rt_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'ketua_rw'])
        ->assertSessionHasErrors('rw_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'ketua_rw', 'rw_id' => $this->rw->id])
        ->assertSessionHasErrors('rt_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'ketua_rw', 'rt_id' => null])
        ->assertSessionHasErrors('rw_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'admin', 'rt_id' => null, 'rw_id' => $this->rw->id])
        ->assertSessionHasErrors('rw_id');

    $this->rt->update(['is_active' => false]);
    $this->post(route('admin.accounts.store'), $this->payload)->assertSessionHasErrors('rt_id');
    $this->rt->update(['is_active' => true]);
    $this->rw->update(['is_active' => false]);
    $this->post(route('admin.accounts.store'), $this->payload)->assertSessionHasErrors('rt_id');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'role' => 'ketua_rw', 'rt_id' => null, 'rw_id' => $this->rw->id])
        ->assertSessionHasErrors('rw_id');

    $this->assertDatabaseMissing('users', ['email' => $this->payload['email']]);
    Notification::assertNothingSent();
});

test('duplicate email and invalid initial password are rejected without flashing passwords', function () {
    $this->actingAs($this->superAdmin);
    $existing = User::factory()->create();

    $this->post(route('admin.accounts.store'), [...$this->payload, 'email' => $existing->email])
        ->assertSessionHasErrors('email');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'password_confirmation' => 'tidak-cocok'])
        ->assertSessionHasErrors('password');
    $this->post(route('admin.accounts.store'), [...$this->payload, 'password' => 'pendek', 'password_confirmation' => 'pendek'])
        ->assertSessionHasErrors('password');
    $this->post(route('admin.accounts.store'), [
        ...$this->payload, 'role' => 'ketua_rw', 'rt_id' => null, 'rw_id' => 999999,
    ])->assertSessionHasErrors('rw_id');

    expect(session()->getOldInput('password'))->toBeNull()
        ->and(session()->getOldInput('password_confirmation'))->toBeNull();
});
