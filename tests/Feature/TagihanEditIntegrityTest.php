<?php

use App\Models\AuditLog;
use App\Models\KasMasuk;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->firstOrFail()->id]);
    $resident = User::factory()->create(['role_id' => Role::where('name', 'warga')->firstOrFail()->id]);
    $this->bill = Tagihan::create([
        'user_id' => $resident->id, 'bulan' => 10, 'tahun' => 2026,
        'total' => 50000, 'status' => 'belum_bayar', 'note' => 'Original',
    ]);
});

test('paid bill cannot diverge from its cash entry through editing', function () {
    $this->actingAs($this->admin)->post(route('tagihan.confirm'), [
        'tagihan_id' => $this->bill->id, 'status' => 'lunas',
    ])->assertSessionHasNoErrors();
    $this->patch(route('tagihan.update', $this->bill), ['total' => 90000, 'note' => 'Changed'])
        ->assertSessionHasErrors('total');
    expect((int) $this->bill->fresh()->total)->toBe(50000)
        ->and($this->bill->fresh()->note)->toBe('Original')
        ->and((int) KasMasuk::where('tagihan_id', $this->bill->id)->sole()->jumlah)->toBe(50000);
    $this->get(route('tagihan.edit', $this->bill))->assertForbidden();
    $this->get(route('tagihan.admin'))->assertOk()
        ->assertDontSee('href="'.route('tagihan.edit', $this->bill).'"', false)
        ->assertSee('Nominal tagihan dikunci');
});

test('processing and exempt bills cannot be edited', function (string $status) {
    $this->bill->update(['status' => $status]);
    $this->actingAs($this->admin)->patch(route('tagihan.update', $this->bill), ['total' => 90000])
        ->assertSessionHasErrors('total');
    expect((int) $this->bill->fresh()->total)->toBe(50000);
    $this->get(route('tagihan.edit', $this->bill))->assertForbidden();
    expect(AuditLog::where('auditable_type', Tagihan::class)->where('auditable_id', $this->bill->id)
        ->where('event', 'tagihan_updated')->exists())->toBeFalse();
})->with(['pending_transfer', 'pending_offline', 'dikecualikan']);

test('unpaid or rejected bill can be edited before entering the ledger', function (string $status) {
    $this->bill->update(['status' => $status, 'verification_status' => $status === 'failed' ? 'ditolak' : 'belum_dikirim']);
    $this->actingAs($this->admin)->patch(route('tagihan.update', $this->bill), ['total' => 90000])
        ->assertSessionHasNoErrors()->assertRedirect(route('tagihan.admin'));
    expect((int) $this->bill->fresh()->total)->toBe(90000);
    $this->post(route('tagihan.confirm'), [
        'tagihan_id' => $this->bill->id, 'status' => 'lunas',
    ])->assertSessionHasNoErrors();
    expect((int) KasMasuk::where('tagihan_id', $this->bill->id)->sole()->jumlah)->toBe(90000);
})->with(['belum_bayar', 'failed']);

test('bill with historical cash entry cannot be edited even if unpaid again', function (bool $deleted) {
    $cash = KasMasuk::create([
        'tagihan_id' => $this->bill->id, 'user_id' => $this->bill->user_id,
        'jumlah' => 50000, 'tanggal' => now(), 'keterangan' => 'Historical payment',
    ]);
    if ($deleted) {
        $cash->delete();
    }
    $this->actingAs($this->admin)->patch(route('tagihan.update', $this->bill), ['total' => 90000])
        ->assertSessionHasErrors('total');
    expect((int) $this->bill->fresh()->total)->toBe(50000)
        ->and((int) KasMasuk::withTrashed()->findOrFail($cash->id)->jumlah)->toBe(50000);
})->with([true, false]);

test('another rt treasurer cannot edit a bill outside their scope', function () {
    $rw = Rw::create(['name' => 'RW Edit']);
    $rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $other = Rt::create(['name' => 'RT B', 'rw_id' => $rw->id]);
    $treasurer = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->firstOrFail()->id, 'rt_id' => $rt->id,
    ]);
    $this->bill->update(['rt_id' => $other->id]);
    $this->actingAs($treasurer)->patch(route('tagihan.update', $this->bill), ['total' => 90000])->assertNotFound();
    $this->get(route('tagihan.edit', $this->bill))->assertNotFound();
    expect((int) $this->bill->fresh()->total)->toBe(50000);
});
