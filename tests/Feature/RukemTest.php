<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Rt;
use App\Models\RukemClaim;
use App\Models\RukemIuran;
use App\Models\RukemMember;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->rw = Rw::create([
        'name' => '05',
        'description' => 'RW 05',
    ]);

    $this->rt1 = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '01',
        'description' => 'RT 01',
    ]);

    $this->rt2 = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => '02',
        'description' => 'RT 02',
    ]);

    // Permissions
    $pView = Permission::firstOrCreate(['name' => 'view-rukem'], ['description' => 'View rukem']);
    $pSubmit = Permission::firstOrCreate(['name' => 'submit-rukem'], ['description' => 'Submit rukem']);
    $pManage = Permission::firstOrCreate(['name' => 'manage-rukem'], ['description' => 'Manage rukem']);
    $pApprove = Permission::firstOrCreate(['name' => 'approve-rukem'], ['description' => 'Approve rukem']);
    $pExport = Permission::firstOrCreate(['name' => 'export-rukem'], ['description' => 'Export rukem']);

    // Roles
    $this->roleWarga = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);
    $this->roleWarga->permissions()->syncWithoutDetaching([$pView->id, $pSubmit->id]);

    $this->rolePengurus = Role::firstOrCreate(['name' => 'ketua_rt'], ['description' => 'Ketua RT']);
    $this->rolePengurus->permissions()->syncWithoutDetaching([
        $pView->id, $pSubmit->id, $pManage->id, $pApprove->id, $pExport->id,
    ]);

    // Users
    $this->warga1 = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rt1->id,
        'status_akun' => 'aktif',
    ]);

    $this->warga2 = User::factory()->create([
        'role_id' => $this->roleWarga->id,
        'rt_id' => $this->rt2->id,
        'status_akun' => 'aktif',
    ]);

    $this->pengurus1 = User::factory()->create([
        'role_id' => $this->rolePengurus->id,
        'rt_id' => $this->rt1->id,
        'status_akun' => 'aktif',
    ]);

    $this->pengurus2 = User::factory()->create([
        'role_id' => $this->rolePengurus->id,
        'rt_id' => $this->rt2->id,
        'status_akun' => 'aktif',
    ]);
});

test('warga can view rukem portal index', function () {
    $response = $this->actingAs($this->warga1)->get(route('rukem.index'));

    $response->assertOk();
    $response->assertSee('Rukun Kematian');
    $response->assertSee('Berita Duka');
});

test('guest cannot view rukem portal index', function () {
    $response = $this->get(route('rukem.index'));

    $response->assertRedirect(route('login'));
});

test('warga can register as rukem member', function () {
    $response = $this->actingAs($this->warga1)->post(route('rukem.store-daftar'));

    $response->assertRedirect(route('rukem.index'));
    $this->assertDatabaseHas('rukem_members', [
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'status' => 'active',
    ]);
});

test('warga cannot register twice as rukem member', function () {
    RukemMember::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'member_number' => 'RKM-0001',
        'joined_at' => now(),
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->warga1)->post(route('rukem.store-daftar'));

    $response->assertRedirect(route('rukem.index'));
    expect(RukemMember::where('user_id', $this->warga1->id)->count())->toBe(1);
});

test('warga can submit rukem iuran payment with proof', function () {
    $file = UploadedFile::fake()->image('bukti-transfer.jpg');

    $response = $this->actingAs($this->warga1)->post(route('rukem.store-iuran'), [
        'period_month' => 11,
        'period_year' => 2026,
        'amount' => 25000,
        'proof' => $file,
        'notes' => 'Iuran kematian bulan November',
    ]);

    $response->assertRedirect(route('rukem.index'));
    $this->assertDatabaseHas('rukem_iurans', [
        'user_id' => $this->warga1->id,
        'period_month' => 11,
        'period_year' => 2026,
        'amount' => 25000,
        'status' => 'pending',
    ]);
});

test('warga cannot submit duplicate iuran for the same period', function () {
    RukemIuran::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'period_month' => 11,
        'period_year' => 2026,
        'amount' => 20000,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->warga1)->post(route('rukem.store-iuran'), [
        'period_month' => 11,
        'period_year' => 2026,
        'amount' => 20000,
    ]);

    $response->assertSessionHasErrors(['period_month']);
});

test('warga can report a death claim', function () {
    $response = $this->actingAs($this->warga1)->post(route('rukem.store-claim'), [
        'deceased_name' => 'Bapak Sudirman',
        'relationship' => 'orang_tua',
        'death_date' => now()->toDateString(),
        'cemetery_location' => 'TPU Menteng Pulo',
        'description' => 'Rumah duka RT 01 no 15',
    ]);

    $claim = RukemClaim::where('deceased_name', 'Bapak Sudirman')->first();
    expect($claim)->not->toBeNull();
    $response->assertRedirect(route('rukem.show-claim', $claim));
    expect($claim->status)->toBe('submitted');
});

test('warga can view their own claim detail', function () {
    $claim = RukemClaim::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'deceased_name' => 'Ibu Fatimah',
        'relationship' => 'orang_tua',
        'death_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->warga1)->get(route('rukem.show-claim', $claim));
    $response->assertOk();
    $response->assertSee('Ibu Fatimah');
});

test('unverified claim of RT 01 cannot be viewed by regular warga of RT 02', function () {
    $claim = RukemClaim::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'deceased_name' => 'Rahasia Duka RT 01',
        'relationship' => 'pasangan',
        'death_date' => now()->toDateString(),
        'status' => 'submitted', // not verified yet
    ]);

    $response = $this->actingAs($this->warga2)->get(route('rukem.show-claim', $claim));
    $response->assertForbidden();
});

test('pengurus RT 01 can view admin dashboard and verify claim', function () {
    $claim = RukemClaim::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'deceased_name' => 'H. Kasim',
        'relationship' => 'orang_tua',
        'death_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->pengurus1)->get(route('admin.rukem.index'));
    $response->assertOk();
    $response->assertSee('H. Kasim');

    $verifyResponse = $this->actingAs($this->pengurus1)->patch(route('admin.rukem.verify-claim', $claim), [
        'status' => 'verified',
    ]);

    $verifyResponse->assertRedirect(route('admin.rukem.show-claim', $claim));
    $claim->refresh();
    expect($claim->status)->toBe('verified');
    expect($claim->verified_by)->toBe($this->pengurus1->id);
});

test('pengurus RT 01 can approve claim and disburse santunan', function () {
    $claim = RukemClaim::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'deceased_name' => 'Almarhum Ujang',
        'relationship' => 'anak',
        'death_date' => now()->toDateString(),
        'status' => 'verified',
    ]);

    // Step 1: Approve
    $approveRes = $this->actingAs($this->pengurus1)->patch(route('admin.rukem.approve-claim', $claim), [
        'status' => 'approved',
    ]);
    $approveRes->assertRedirect(route('admin.rukem.show-claim', $claim));
    $claim->refresh();
    expect($claim->status)->toBe('approved');

    // Step 2: Disburse Bantuan
    $disburseRes = $this->actingAs($this->pengurus1)->post(route('admin.rukem.disalurkan-bantuan', $claim), [
        'amount' => 500000,
        'recipient_name' => 'Ahmad Ahli Waris',
        'recipient_phone' => '08123456789',
        'given_at' => now()->toDateString(),
        'note' => 'Diserahkan langsung di rumah duka',
    ]);

    $disburseRes->assertRedirect(route('admin.rukem.show-claim', $claim));
    $claim->refresh();
    expect($claim->status)->toBe('done');

    $this->assertDatabaseHas('rukem_bantuans', [
        'claim_id' => $claim->id,
        'amount' => 500000,
        'recipient_name' => 'Ahmad Ahli Waris',
        'given_by' => $this->pengurus1->id,
    ]);
});

test('pengurus RT 02 cannot verify claim belonging to RT 01', function () {
    $claim = RukemClaim::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'deceased_name' => 'Warga RT 01',
        'relationship' => 'tetangga',
        'death_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->pengurus2)->patch(route('admin.rukem.verify-claim', $claim), [
        'status' => 'verified',
    ]);

    $response->assertForbidden();
    $claim->refresh();
    expect($claim->status)->toBe('submitted');
});

test('pengurus can verify member iuran payment', function () {
    $iuran = RukemIuran::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'period_month' => 12,
        'period_year' => 2026,
        'amount' => 20000,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->pengurus1)->patch(route('admin.rukem.verify-iuran', $iuran), [
        'status' => 'verified',
    ]);

    $response->assertRedirect();
    $iuran->refresh();
    expect($iuran->status)->toBe('verified');
    expect($iuran->verified_by)->toBe($this->pengurus1->id);
});

test('pengurus can toggle member active or inactive status', function () {
    $member = RukemMember::create([
        'user_id' => $this->warga1->id,
        'rt_id' => $this->rt1->id,
        'rw_id' => $this->rw->id,
        'member_number' => 'RKM-0099',
        'joined_at' => now(),
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->pengurus1)->patch(route('admin.rukem.member-status', $member), [
        'status' => 'inactive',
    ]);

    $response->assertRedirect();
    $member->refresh();
    expect($member->status)->toBe('inactive');
});

test('pengurus can access rukem financial export report', function () {
    $response = $this->actingAs($this->pengurus1)->get(route('admin.rukem.export', ['year' => 2026]));

    $response->assertOk();
    $response->assertSee('Laporan Rekapitulasi Kas Sosial Duka');
});
