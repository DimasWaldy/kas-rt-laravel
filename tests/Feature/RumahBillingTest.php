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

test('additional iuran creates separate bill except kebersihan and keamanan are grouped', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'T-03',
        'alamat' => 'Jl. Test No. 3',
        'rt' => '001',
        'rw' => '002',
    ]);

    $penanggungJawab = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_kepala_keluarga' => true,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $rumah->update(['penanggung_jawab_id' => $penanggungJawab->id]);

    IuranBulanan::create([
        'nama' => 'Iuran Kebersihan',
        'jumlah' => 20000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 15000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    Tagihan::generateForMonth(5, 2026);

    $routineBill = Tagihan::where('rumah_id', $rumah->id)
        ->where('billing_group', 'iuran_rutin')
        ->firstOrFail();

    $routineBill->update([
        'status' => 'lunas',
        'payment_method' => 'offline',
        'paid_at' => now(),
    ]);

    $maulid = IuranBulanan::create([
        'nama' => 'Maulid',
        'jumlah' => 50000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => false,
    ]);

    Tagihan::generateForMonth(5, 2026);

    $routineBill = $routineBill->fresh();
    $maulidBill = Tagihan::where('rumah_id', $rumah->id)
        ->where('billing_group', 'iuran_' . $maulid->id)
        ->firstOrFail();

    expect(Tagihan::where('rumah_id', $rumah->id)->count())->toBe(2);
    expect($routineBill->total)->toBe(35000);
    expect($routineBill->status)->toBe('lunas');
    expect($maulidBill->total)->toBe(50000);
    expect($maulidBill->status)->toBe('belum_bayar');
    expect($maulidBill->judul)->toBe('Maulid');
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
