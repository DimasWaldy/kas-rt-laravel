<?php

use App\Models\AuditLog;
use App\Models\IuranBulanan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Warga;
use App\Notifications\TagihanCreated;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Notification::fake();
    $rw = Rw::create(['name' => 'RW Billing', 'is_active' => true]);
    $this->billingRt = Rt::create(['name' => 'RT Billing', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->billingOtherRt = Rt::create(['name' => 'RT Other Billing', 'rw_id' => $rw->id, 'is_active' => true]);
    $this->billingHouse = Rumah::create(['kode_rumah' => 'NEEDS-PJ', 'alamat' => 'Alamat Perlu PJ', 'rt_id' => $this->billingRt->id, 'status' => 'aktif']);
    $this->billingResident = User::factory()->create(['role_id' => Role::where('name', 'warga')->value('id'), 'rt_id' => $this->billingRt->id, 'rumah_id' => $this->billingHouse->id]);
    Warga::create(['user_id' => $this->billingResident->id, 'nama_lengkap' => $this->billingResident->name, 'status_verifikasi' => 'terverifikasi']);
    $this->billingManager = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rt')->value('id'), 'rt_id' => $this->billingRt->id]);
    $this->billingTreasurer = User::factory()->create(['role_id' => Role::where('name', 'bendahara_rt')->value('id'), 'rt_id' => $this->billingRt->id]);
    IuranBulanan::create(['nama' => 'Iuran Keamanan', 'jumlah' => 15000, 'bulan' => now()->month, 'tahun' => now()->year, 'is_wajib' => true]);
    $this->makeBillingReady = function (Rumah $house, ?User $resident = null) {
        $resident ??= User::factory()->create(['role_id' => $this->billingResident->role_id, 'rt_id' => $house->rt_id, 'rumah_id' => $house->id]);
        $resident->update(['is_penanggung_jawab_rumah' => true]);
        $house->update(['penanggung_jawab_id' => $resident->id]);

        return $resident;
    };
});

test('generation explicitly reports active house without pj and never chooses an owner automatically', function () {
    Rumah::create(['kode_rumah' => 'EMPTY', 'rt_id' => $this->billingRt->id, 'status' => 'kosong']);
    $result = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    expect($result['created'])->toBe(0)->and($result['skipped'])->toBe(0)->and($result['active_houses'])->toBe(1)
        ->and($result['has_iuran'])->toBeTrue()->and($result['unbilled_houses'])->toBe([
            ['id' => $this->billingHouse->id, 'kode_rumah' => 'NEEDS-PJ', 'reason' => 'Penanggung jawab iuran belum ditetapkan.'],
        ])->and($this->billingHouse->fresh()->penanggung_jawab_id)->toBeNull()
        ->and($this->billingResident->fresh()->is_penanggung_jawab_rumah)->toBeFalse()->and(Tagihan::count())->toBe(0);
});

test('ready houses are billed while blocked houses are counted separately from existing bills', function () {
    $ready = Rumah::create(['kode_rumah' => 'READY', 'rt_id' => $this->billingRt->id, 'status' => 'aktif']);
    ($this->makeBillingReady)($ready);
    $first = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    $second = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    expect($first['created'])->toBe(1)->and($first['skipped'])->toBe(0)->and(count($first['unbilled_houses']))->toBe(1)
        ->and($second['created'])->toBe(0)->and($second['skipped'])->toBe(1)
        ->and(count($second['unbilled_houses']))->toBe(1)->and(Tagihan::count())->toBe(1);
});

test('invalid pj is reported and cannot receive a generated bill', function (string $change) {
    ($this->makeBillingReady)($this->billingHouse, $this->billingResident);
    $data = match ($change) {
        'inactive' => ['status_akun' => 'ditolak'],
        'different_rt' => ['rt_id' => $this->billingOtherRt->id],
        'moved_out' => ['rumah_id' => null],
        default => ['is_penanggung_jawab_rumah' => false],
    };
    $this->billingResident->update($data);
    $result = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    expect($result['created'])->toBe(0)->and($result['unbilled_houses'])->toHaveCount(1)
        ->and($result['unbilled_houses'][0]['reason'])->toContain('perlu diperbarui')
        ->and(Rumah::needsBillingContact()->whereKey($this->billingHouse->id)->exists())->toBeTrue()
        ->and(Tagihan::count())->toBe(0);
})->with(['inactive', 'different_rt', 'moved_out', 'flag_missing']);

test('readiness issues remain visible when the requested period has no iuran', function () {
    $result = Tagihan::generate(1, now()->year + 1, $this->billingTreasurer);
    expect($result['has_iuran'])->toBeFalse()->and($result['unbilled_houses'])->toHaveCount(1)->and($result['created'])->toBe(0);
});

test('billing reports are isolated to rt and rw while scheduler can inspect all houses', function () {
    $sameRwHouse = Rumah::create(['kode_rumah' => 'OTHER-RT', 'rt_id' => $this->billingOtherRt->id]);
    $otherRw = Rw::create(['name' => 'Private RW', 'is_active' => true]);
    $otherRt = Rt::create(['name' => 'Private RT', 'rw_id' => $otherRw->id, 'is_active' => true]);
    $privateHouse = Rumah::create(['kode_rumah' => 'PRIVATE-RW', 'rt_id' => $otherRt->id]);
    $rtResult = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    expect(array_column($rtResult['unbilled_houses'], 'id'))->toBe([$this->billingHouse->id]);
    $rwActor = User::factory()->create(['role_id' => Role::where('name', 'sekretaris_rw')->value('id'), 'rt_id' => $this->billingRt->id]);
    $rwResult = Tagihan::generate(now()->month, now()->year, $rwActor);
    expect(array_column($rwResult['unbilled_houses'], 'id'))->toBe([$this->billingHouse->id, $sameRwHouse->id]);
    $this->actingAs($rwActor)->get(route('admin.rumah.index', ['perlu_pj' => 1]))->assertOk()->assertDontSee('PRIVATE-RW');
    $this->get(route('admin.rumah.edit', $privateHouse))->assertNotFound();
    $global = Tagihan::generate(now()->month, now()->year);
    expect($global['unbilled_houses'])->toHaveCount(3);
});

test('mass generation and its audit report the missing houses instead of claiming complete success', function () {
    $this->actingAs($this->billingTreasurer)->post(route('iuran-bulanan.generate'), ['bulan' => now()->month, 'tahun' => now()->year])
        ->assertRedirect(route('iuran-bulanan.index', ['bulan' => now()->month, 'tahun' => now()->year]))->assertSessionHas('billing_result.unbilled_count', 1)
        ->assertSessionHas('billing_result.created', 0);
    $this->get(route('iuran-bulanan.index'))->assertOk()->assertSee('1 rumah belum dapat ditagih')->assertSee('NEEDS-PJ')
        ->assertSee('Penanggung jawab iuran belum ditetapkan.')->assertSee('Minta pengurus yang mengelola Data Rumah')
        ->assertDontSee(route('admin.rumah.edit', $this->billingHouse), false);
    $audit = AuditLog::where('event', 'mass_tagihan_generated')->firstOrFail();
    expect($audit->new_values['result']['unbilled_houses'][0]['id'])->toBe($this->billingHouse->id);
});

test('adding iuran also reports houses that cannot be billed', function () {
    $this->actingAs($this->billingTreasurer)->post(route('iuran-bulanan.store'), [
        'nama' => 'Dana Sosial', 'jumlah' => 5000, 'bulan' => now()->month, 'tahun' => now()->year, 'is_wajib' => 1,
    ])->assertSessionHasNoErrors()->assertSessionHas('billing_result.unbilled_count', 1)->assertSessionHas('billing_result.created', 0);
    expect(Tagihan::count())->toBe(0);
});

test('rt treasurer cannot modify global monthly contribution components', function () {
    $iuran = IuranBulanan::firstOrFail();

    $this->actingAs($this->billingTreasurer)
        ->patch(route('iuran-bulanan.update', $iuran), [
            'nama' => 'Iuran Diubah', 'jumlah' => 25000, 'keterangan' => null, 'is_wajib' => true,
        ])->assertForbidden();

    $this->actingAs($this->billingTreasurer)
        ->delete(route('iuran-bulanan.destroy', $iuran))
        ->assertForbidden();

    expect($iuran->fresh()->nama)->toBe('Iuran Keamanan');
});

test('deleting an iuran soft deletes its unpaid generated bills', function () {
    ($this->makeBillingReady)($this->billingHouse, $this->billingResident);
    $iuran = IuranBulanan::create([
        'nama' => 'Dana Sosial', 'jumlah' => 5000, 'bulan' => now()->month, 'tahun' => now()->year, 'is_wajib' => true,
    ]);
    Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    $unpaid = Tagihan::where('billing_group', 'iuran_'.$iuran->id)->firstOrFail();

    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $this->actingAs($admin)->delete(route('iuran-bulanan.destroy', $iuran))
        ->assertRedirect(route('iuran-bulanan.index', ['bulan' => now()->month, 'tahun' => now()->year]));

    expect(Tagihan::withTrashed()->whereKey($unpaid->id)->exists())->toBeTrue()
        ->and(Tagihan::withTrashed()->whereKey($unpaid->id)->first()->trashed())->toBeTrue();
});

test('iuran with a paid bill cannot be deleted', function () {
    ($this->makeBillingReady)($this->billingHouse, $this->billingResident);
    $iuran = IuranBulanan::create([
        'nama' => 'Dana Sosial', 'jumlah' => 5000, 'bulan' => now()->month, 'tahun' => now()->year, 'is_wajib' => true,
    ]);
    Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    $paid = Tagihan::where('billing_group', 'iuran_'.$iuran->id)->firstOrFail();
    $paid->update(['status' => 'lunas', 'verification_status' => 'valid']);

    $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);
    $this->actingAs($admin)->delete(route('iuran-bulanan.destroy', $iuran))
        ->assertRedirect(route('iuran-bulanan.index', ['bulan' => now()->month, 'tahun' => now()->year]))
        ->assertSessionHas('error');

    expect(IuranBulanan::find($iuran->id))->not->toBeNull()
        ->and(Tagihan::withTrashed()->whereKey($paid->id)->exists())->toBeTrue()
        ->and(Tagihan::withTrashed()->whereKey($paid->id)->first()->trashed())->toBeFalse();
});

test('house task filter lists only active houses in scope that need billing contact', function () {
    $ready = Rumah::create(['kode_rumah' => 'READY-HIDDEN', 'rt_id' => $this->billingRt->id]);
    ($this->makeBillingReady)($ready);
    Rumah::create(['kode_rumah' => 'INACTIVE-HIDDEN', 'rt_id' => $this->billingRt->id, 'status' => 'nonaktif']);
    Rumah::create(['kode_rumah' => 'PRIVATE-HIDDEN', 'rt_id' => $this->billingOtherRt->id]);
    $this->actingAs($this->billingManager)->get(route('admin.rumah.index', ['perlu_pj' => 1]))->assertOk()
        ->assertSee('NEEDS-PJ')->assertSee('Tetapkan PJ')->assertDontSee('READY-HIDDEN')
        ->assertDontSee('INACTIVE-HIDDEN')->assertDontSee('PRIVATE-HIDDEN')
        ->assertViewHas('stats', fn ($stats) => $stats['tanpa_pj'] === 1);
});

test('assigning pj clears the task and regeneration creates the missing bill once', function () {
    $this->actingAs($this->billingManager)->patch(route('admin.rumah.update', $this->billingHouse), [
        'kode_rumah' => $this->billingHouse->kode_rumah, 'status' => 'aktif', 'penanggung_jawab_id' => $this->billingResident->id,
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.rumah.show', $this->billingHouse));
    expect(Tagihan::count())->toBe(0)->and(Rumah::needsBillingContact()->whereKey($this->billingHouse->id)->exists())->toBeFalse()
        ->and($this->billingResident->fresh()->is_penanggung_jawab_rumah)->toBeTrue();
    $result = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    $repeat = Tagihan::generate(now()->month, now()->year, $this->billingTreasurer);
    expect($result['created'])->toBe(1)->and($result['unbilled_houses'])->toBe([])
        ->and($repeat['created'])->toBe(0)->and($repeat['skipped'])->toBe(1)->and(Tagihan::count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', ['event' => 'pj_iuran_ditetapkan', 'user_id' => $this->billingManager->id]);
    Notification::assertSentTo($this->billingResident, TagihanCreated::class);
});

test('pj assignment rejects an inactive account another house or another rt', function (string $change) {
    $this->billingResident->update(match ($change) {
        'inactive' => ['status_akun' => 'ditolak'], 'different_rt' => ['rt_id' => $this->billingOtherRt->id], default => ['rumah_id' => null],
    });
    $this->actingAs($this->billingManager)->patch(route('admin.rumah.update', $this->billingHouse), [
        'kode_rumah' => $this->billingHouse->kode_rumah, 'status' => 'aktif', 'penanggung_jawab_id' => $this->billingResident->id,
    ])->assertSessionHasErrors('penanggung_jawab_id');
    expect($this->billingHouse->fresh()->penanggung_jawab_id)->toBeNull();
})->with(['inactive', 'different_rt', 'moved_out']);

test('scheduled command explains incomplete billing and succeeds after pj is assigned', function () {
    $this->artisan('bills:generate')->expectsOutput('Rumah belum dapat ditagih: 1')
        ->expectsTable(['Kode rumah', 'Alasan belum dapat ditagih'], [['NEEDS-PJ', 'Penanggung jawab iuran belum ditetapkan.']])->assertExitCode(1);
    ($this->makeBillingReady)($this->billingHouse, $this->billingResident);
    $this->artisan('bills:generate')->expectsOutput('Tagihan baru dibuat: 1')->expectsOutput('Rumah belum dapat ditagih: 0')->assertExitCode(0);
    $this->artisan('bills:generate')->expectsOutput('Tagihan baru dibuat: 0')->assertExitCode(0);
    expect(Tagihan::count())->toBe(1);
});

test('resident sees missing pj explanation on profile and bills page', function () {
    $this->actingAs($this->billingResident)->get(route('profile.edit'))->assertOk()->assertSee('Rumah belum siap menerima tagihan')
        ->assertSee('Penanggung jawab iuran belum ditetapkan.');
    $this->get(route('tagihan.index'))->assertOk()->assertSee('Penanggung jawab iuran belum ditetapkan.')
        ->assertSee('Hubungi pengurus')->assertDontSee(route('admin.rumah.edit', $this->billingHouse), false);
});

test('a previously missed period can be chosen and generated after pj assignment', function () {
    $period = now()->startOfMonth()->subMonth();
    IuranBulanan::create(['nama' => 'Iuran Keamanan', 'jumlah' => 12000, 'bulan' => $period->month, 'tahun' => $period->year, 'is_wajib' => true]);
    $first = Tagihan::generate($period->month, $period->year, $this->billingTreasurer);
    expect($first['unbilled_houses'])->toHaveCount(1);
    $this->actingAs($this->billingManager)->patch(route('admin.rumah.update', $this->billingHouse), [
        'kode_rumah' => $this->billingHouse->kode_rumah, 'status' => 'aktif', 'penanggung_jawab_id' => $this->billingResident->id,
    ])->assertSessionHasNoErrors();
    $this->actingAs($this->billingTreasurer)->post(route('iuran-bulanan.generate'), ['bulan' => $period->month, 'tahun' => $period->year])
        ->assertSessionHas('billing_result.created', 1)->assertSessionHas('billing_result.unbilled_count', 0)
        ->assertRedirect(route('iuran-bulanan.index', ['bulan' => $period->month, 'tahun' => $period->year]));
    $this->get(route('iuran-bulanan.index', ['bulan' => $period->month, 'tahun' => $period->year]))->assertOk()
        ->assertViewHas('bulan', $period->month)->assertViewHas('tahun', $period->year);
    expect(Tagihan::where('bulan', $period->month)->where('tahun', $period->year)->count())->toBe(1)
        ->and(Tagihan::where('bulan', now()->month)->where('tahun', now()->year)->count())->toBe(0);
});

test('cli can retry a chosen historical period instead of billing the current month', function () {
    $period = now()->startOfMonth()->subMonth();
    IuranBulanan::create(['nama' => 'Iuran Keamanan', 'jumlah' => 12000, 'bulan' => $period->month, 'tahun' => $period->year, 'is_wajib' => true]);
    ($this->makeBillingReady)($this->billingHouse, $this->billingResident);
    $this->artisan('bills:generate', ['--bulan' => $period->month, '--tahun' => $period->year])
        ->expectsOutput('Tagihan baru dibuat: 1')->assertExitCode(0);
    expect(Tagihan::where('bulan', $period->month)->where('tahun', $period->year)->count())->toBe(1)
        ->and(Tagihan::where('bulan', now()->month)->where('tahun', now()->year)->count())->toBe(0);
});

test('invalid billing period is rejected before components or bills are changed', function () {
    $this->artisan('bills:generate', ['--bulan' => 13])->assertExitCode(2);
    $this->actingAs($this->billingTreasurer)->get(route('iuran-bulanan.index', ['bulan' => 13]))->assertSessionHasErrors('bulan');
    expect(IuranBulanan::count())->toBe(1)->and(Tagihan::count())->toBe(0);
});
