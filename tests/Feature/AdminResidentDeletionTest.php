<?php

use App\Models\KoperasiPinjam;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Support\FinancialHistory;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $rw = Rw::create(['name' => 'RW Delete']);
    $this->rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $this->otherRt = Rt::create(['name' => 'RT B', 'rw_id' => $rw->id]);
    $this->secretary = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->sole()->id, 'rt_id' => $this->rt->id,
    ]);
    $this->resident = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->sole()->id, 'rt_id' => $this->rt->id,
    ]);
});

test('authorized secretary can delete resident without financial history', function () {
    $this->actingAs($this->secretary)->delete(route('admin.warga.destroy', $this->resident))
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.warga.index'));
    $this->assertDatabaseMissing('users', ['id' => $this->resident->id]);
    $this->assertAuthenticatedAs($this->secretary);
});

test('secretary cannot delete a foreign rt or unassigned resident', function (bool $unassigned) {
    $this->resident->update(['rt_id' => $unassigned ? null : $this->otherRt->id]);
    $this->actingAs($this->secretary)->delete(route('admin.warga.destroy', $this->resident))->assertNotFound();
    $this->assertDatabaseHas('users', ['id' => $this->resident->id]);
})->with([true, false]);

test('resident management endpoint cannot delete a non resident account', function () {
    $this->actingAs($this->secretary)->delete(route('admin.warga.destroy', $this->secretary))->assertNotFound();
    $this->assertDatabaseHas('users', ['id' => $this->secretary->id]);
});

test('ordinary resident cannot call admin deletion endpoint', function () {
    $this->actingAs($this->resident)->delete(route('admin.warga.destroy', $this->resident))->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $this->resident->id]);
});

test('staff deletion retains loans and shows an actionable error', function (bool $globalAdmin) {
    $actor = $globalAdmin
        ? User::factory()->create(['role_id' => Role::where('name', 'admin')->sole()->id])
        : $this->secretary;
    $loan = KoperasiPinjam::create([
        'user_id' => $this->resident->id, 'amount' => 1000000, 'tenor_months' => 10,
        'service_fee_percentage' => 2, 'service_fee_amount' => 200000,
        'remaining_amount' => 1080000, 'status' => 'disetujui',
    ]);
    $installment = $loan->angsurans()->create(['amount' => 120000, 'paid_at' => now(), 'status' => 'terverifikasi']);
    $this->actingAs($actor)->from(route('admin.warga.index'))
        ->delete(route('admin.warga.destroy', $this->resident))
        ->assertRedirect(route('admin.warga.index'))->assertSessionHasErrors('account');
    $this->assertDatabaseHas('users', ['id' => $this->resident->id]);
    $this->assertDatabaseHas('koperasi_pinjams', ['id' => $loan->id, 'remaining_amount' => 1080000]);
    $this->assertDatabaseHas('koperasi_angsurans', ['id' => $installment->id]);

    $errors = (new ViewErrorBag)->put('default', new MessageBag(['account' => FinancialHistory::DELETION_MESSAGE]));
    $this->view('admin.warga.index', [
        'users' => User::whereKey($this->resident->id)->paginate(15),
        'rumahs' => collect(),
        'errors' => $errors,
    ])->assertSee(FinancialHistory::DELETION_MESSAGE);
})->with([true, false]);
