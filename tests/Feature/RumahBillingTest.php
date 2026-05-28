<?php

use App\Models\IuranBulanan;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;

test('monthly bills are generated once per rumah even when a rumah has multiple kk', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'T-01',
        'alamat' => 'Jl. Test No. 1',
        'rt' => '001',
        'rw' => '002',
    ]);

    $penanggungJawab = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'no_kk' => 'KK-001',
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
    ]);

    User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'no_kk' => 'KK-002',
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => false,
    ]);

    $rumah->update(['penanggung_jawab_id' => $penanggungJawab->id]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 15000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    Tagihan::generateForMonth(5, 2026);

    expect(Tagihan::where('rumah_id', $rumah->id)->count())->toBe(1);
    expect(Tagihan::where('rumah_id', $rumah->id)->first()->user_id)->toBe($penanggungJawab->id);
});

test('non penanggung jawab rumah cannot submit payment for rumah bill', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'T-02',
        'alamat' => 'Jl. Test No. 2',
        'rt' => '001',
        'rw' => '002',
    ]);

    $penanggungJawab = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $anggotaRumah = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => false,
    ]);

    $rumah->update(['penanggung_jawab_id' => $penanggungJawab->id]);

    $tagihan = Tagihan::create([
        'user_id' => $penanggungJawab->id,
        'rumah_id' => $rumah->id,
        'bulan' => 5,
        'tahun' => 2026,
        'total' => 50000,
        'status' => 'belum_bayar',
    ]);

    $response = $this->actingAs($anggotaRumah)->post(route('tagihan.pay'), [
        'tagihan_id' => $tagihan->id,
        'payment_method' => 'offline',
        'note' => 'Diserahkan ke bendahara',
    ]);

    $response->assertRedirect(route('tagihan.index'));
    $response->assertSessionHas('error', 'Hanya penanggung jawab rumah yang dapat membayar tagihan iuran.');

    expect($tagihan->fresh()->status)->toBe('belum_bayar');
});
