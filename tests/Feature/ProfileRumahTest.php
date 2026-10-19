<?php

use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('profile cannot change domicile to read another household payment proof', function (string $roleName, bool $sameRt) {
    Storage::fake('local');
    $rw = Rw::create(['name' => 'RW Test']);
    $rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $otherRt = $sameRt ? $rt : Rt::create(['name' => 'RT B', 'rw_id' => $rw->id]);
    $home = Rumah::create(['kode_rumah' => 'HOME', 'rt_id' => $rt->id]);
    $target = Rumah::create(['kode_rumah' => 'PRIVATE-HOUSE', 'rt_id' => $otherRt->id]);
    $user = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
        'rt_id' => $rt->id, 'rumah_id' => $home->id,
    ]);
    $owner = User::factory()->create(['rt_id' => $otherRt->id, 'rumah_id' => $target->id]);
    $bill = Tagihan::create([
        'user_id' => $owner->id, 'rumah_id' => $target->id, 'rt_id' => $otherRt->id,
        'bulan' => 1, 'tahun' => 2026, 'total' => 50000, 'status' => 'belum_bayar',
        'bukti' => 'tagihan-bukti/private.jpg',
    ]);
    Storage::disk('local')->put($bill->bukti, 'private proof');

    $this->actingAs($user)->get(route('tagihan.bukti', $bill))->assertForbidden();
    $this->from('/profile')->patch(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email,
        'rumah_id' => $target->id, 'rt_id' => $otherRt->id,
    ])->assertSessionHasErrors('rumah_id')->assertRedirect('/profile');
    expect($user->fresh()->rumah_id)->toBe($home->id)
        ->and($user->fresh()->rt_id)->toBe($rt->id);
    $this->get(route('tagihan.bukti', $bill))->assertForbidden();
    $this->get('/profile')->assertOk()->assertDontSee('PRIVATE-HOUSE')
        ->assertSee('Hubungi pengurus untuk menetapkan atau mengubah RT');
})->with([['warga', true], ['warga', false], ['sekretaris_rt', false]]);

test('profile cannot create a house or take responsibility for an existing house', function () {
    $user = User::factory()->create(['rumah_id' => null, 'rt_id' => null]);
    $this->actingAs($user)->from('/profile')->patch('/profile', [
        'name' => $user->name, 'email' => $user->email,
        'rumah_kode' => 'UNAUTHORIZED', 'rumah_alamat' => 'New address',
        'is_penanggung_jawab_rumah' => 1,
    ])->assertSessionHasErrors(['rumah_kode', 'rumah_alamat', 'is_penanggung_jawab_rumah']);
    expect(Rumah::where('kode_rumah', 'UNAUTHORIZED')->exists())->toBeFalse()
        ->and($user->fresh()->rumah_id)->toBeNull()
        ->and($user->fresh()->is_penanggung_jawab_rumah)->toBeFalse();
});

test('personal profile update preserves domicile and house responsibility', function () {
    $rw = Rw::create(['name' => 'RW Test']);
    $rt = Rt::create(['name' => 'RT A', 'rw_id' => $rw->id]);
    $home = Rumah::create(['kode_rumah' => 'OWN', 'rt_id' => $rt->id]);
    $user = User::factory()->create([
        'rt_id' => $rt->id, 'rumah_id' => $home->id, 'is_penanggung_jawab_rumah' => true,
    ]);
    $home->update(['penanggung_jawab_id' => $user->id]);
    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated name', 'email' => $user->email, 'phone' => '081234567890',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
    expect($user->fresh()->name)->toBe('Updated name')
        ->and($user->fresh()->rumah_id)->toBe($home->id)
        ->and($user->fresh()->rt_id)->toBe($rt->id)
        ->and($user->fresh()->is_penanggung_jawab_rumah)->toBeTrue()
        ->and($home->fresh()->penanggung_jawab_id)->toBe($user->id);
});
