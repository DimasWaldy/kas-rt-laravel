<?php

use App\Models\IuranBulanan;
use App\Models\KasMasuk;
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
        'is_penanggung_jawab_rumah' => true,
    ]);

    User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
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

    Tagihan::generate(5, 2026);

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

    Tagihan::generate(5, 2026);

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

    Tagihan::generate(5, 2026);

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

test('regenerating bills does not reset paid or pending bills', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $paidHouse = Rumah::create([
        'kode_rumah' => 'T-04',
        'alamat' => 'Jl. Test No. 4',
        'rt' => '001',
        'rw' => '002',
    ]);

    $pendingHouse = Rumah::create([
        'kode_rumah' => 'T-05',
        'alamat' => 'Jl. Test No. 5',
        'rt' => '001',
        'rw' => '002',
    ]);

    $paidUser = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $paidHouse->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $pendingUser = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $pendingHouse->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $paidHouse->update(['penanggung_jawab_id' => $paidUser->id]);
    $pendingHouse->update(['penanggung_jawab_id' => $pendingUser->id]);

    IuranBulanan::create([
        'nama' => 'Iuran Kebersihan',
        'jumlah' => 20000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    Tagihan::generate(5, 2026);

    $paidBill = Tagihan::where('rumah_id', $paidHouse->id)->firstOrFail();
    $pendingBill = Tagihan::where('rumah_id', $pendingHouse->id)->firstOrFail();

    $paidBill->update([
        'status' => 'lunas',
        'payment_method' => 'offline',
        'note' => 'Sudah diterima bendahara',
        'paid_at' => now(),
    ]);

    $pendingBill->update([
        'status' => 'pending_offline',
        'payment_method' => 'offline',
        'note' => 'Menunggu dicek bendahara',
    ]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 15000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    Tagihan::generate(5, 2026);

    expect($paidBill->fresh()->status)->toBe('lunas');
    expect($paidBill->fresh()->total)->toBe(20000);
    expect($paidBill->fresh()->note)->toBe('Sudah diterima bendahara');
    expect($pendingBill->fresh()->status)->toBe('pending_offline');
    expect($pendingBill->fresh()->total)->toBe(20000);
    expect($pendingBill->fresh()->note)->toBe('Menunggu dicek bendahara');
});

test('regenerating bills keeps rejected proof reason until resident resubmits', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'T-06',
        'alamat' => 'Jl. Test No. 6',
        'rt' => '001',
        'rw' => '002',
    ]);

    $penanggungJawab = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
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

    Tagihan::generate(5, 2026);

    $tagihan = Tagihan::where('rumah_id', $rumah->id)->firstOrFail();
    $tagihan->update([
        'status' => 'failed',
        'payment_method' => 'transfer',
        'verification_status' => 'ditolak',
        'transaction_number' => 'TRX-20260501-ABC123',
        'rejection_reason' => 'Nominal tidak sesuai.',
        'bukti' => 'uploads/proof.jpg',
    ]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 15000,
        'bulan' => 5,
        'tahun' => 2026,
        'is_wajib' => true,
    ]);

    Tagihan::generate(5, 2026);

    $tagihan->refresh();

    expect($tagihan->total)->toBe(35000);
    expect($tagihan->verification_status)->toBe('ditolak');
    expect($tagihan->rejection_reason)->toBe('Nominal tidak sesuai.');
    expect($tagihan->transaction_number)->toBe('TRX-20260501-ABC123');
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
        'is_penanggung_jawab_rumah' => true,
    ]);

    $anggotaRumah = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
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

test('monthly bill command is idempotent and reports created skipped and updated counts', function () {
    $role = Role::firstOrCreate(['name' => 'warga'], ['description' => 'Warga']);

    $rumah = Rumah::create([
        'kode_rumah' => 'T-07',
        'alamat' => 'Jl. Test No. 7',
        'rt' => '001',
        'rw' => '002',
    ]);

    $penanggungJawab = User::factory()->create([
        'role_id' => $role->id,
        'rumah_id' => $rumah->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $rumah->update(['penanggung_jawab_id' => $penanggungJawab->id]);

    IuranBulanan::create([
        'nama' => 'Iuran Keamanan',
        'jumlah' => 15000,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'is_wajib' => true,
    ]);

    $this->artisan('bills:generate')
        ->expectsOutput('Mengecek iuran untuk periode ' . now()->month . '/' . now()->year . '...')
        ->expectsOutput('Selesai! Tagihan keluarga telah diproses.')
        ->expectsOutput('Tagihan baru dibuat: 1')
        ->expectsOutput('Tagihan dilewati karena sudah ada: 0')
        ->expectsOutput('Tagihan yang diperbarui nominal/detailnya: 0')
        ->assertExitCode(0);

    expect(Tagihan::where('rumah_id', $rumah->id)
        ->where('bulan', now()->month)
        ->where('tahun', now()->year)
        ->count())->toBe(1);

    $this->artisan('bills:generate')
        ->expectsOutput('Mengecek iuran untuk periode ' . now()->month . '/' . now()->year . '...')
        ->expectsOutput('Selesai! Tagihan keluarga telah diproses.')
        ->expectsOutput('Tagihan baru dibuat: 0')
        ->expectsOutput('Tagihan dilewati karena sudah ada: 1')
        ->expectsOutput('Tagihan yang diperbarui nominal/detailnya: 0')
        ->assertExitCode(0);

    expect(Tagihan::where('rumah_id', $rumah->id)
        ->where('bulan', now()->month)
        ->where('tahun', now()->year)
        ->count())->toBe(1);
});
