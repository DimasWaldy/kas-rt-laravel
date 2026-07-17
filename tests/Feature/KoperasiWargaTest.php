<?php

use App\Models\User;
use App\Models\KoperasiAngsuran;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Warga;
use Illuminate\Support\Facades\Artisan;

function buatWargaKoperasi() {
    $role = Role::firstOrCreate(['name' => 'warga']);

    $pView = Permission::firstOrCreate(['name' => 'view-koperasi']);
    $pSubmit = Permission::firstOrCreate(['name' => 'submit-koperasi']);
    $role->permissions()->syncWithoutDetaching([$pView->id, $pSubmit->id]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'status_akun' => 'aktif',
    ]);
    return Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'nik' => '1234567890123456',
        'no_kk' => '1234567890123456',
        'agama' => 'Islam',
        'jenis_kelamin' => 'Laki-laki',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'status_perkawinan' => 'Belum Kawin',
        'status_kependudukan' => 'Tetap',
        'kewarganegaraan' => 'WNI',
        'pekerjaan' => 'Swasta',
    ]);
}

beforeEach(function () {
    $this->warga = buatWargaKoperasi();
});

test('warga dapat melihat form daftar koperasi jika belum jadi anggota', function () {
    $this->actingAs($this->warga->user)
        ->get(route('koperasi.index'))
        ->assertOk()
        ->assertSee('Daftar Sekarang');
});

test('warga dapat mendaftar menjadi anggota koperasi', function () {
    $this->actingAs($this->warga->user)
        ->post(route('koperasi.store-daftar'))
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_members', [
        'user_id' => $this->warga->user->id,
        'status' => 'pending',
    ]);
});

test('warga aktif dapat mengajukan simpanan', function () {
    Storage::fake('public');

    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-TEST',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    $file = UploadedFile::fake()->image('bukti.jpg');

    $this->actingAs($this->warga->user)
        ->post(route('koperasi.store-simpanan'), [
            'type' => 'wajib',
            'amount' => 50000,
            'transaction_date' => now()->format('Y-m-d'),
            'proof_file' => $file,
        ])
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_simpanans', [
        'user_id' => $this->warga->user->id,
        'amount' => 50000,
        'status' => 'pending',
    ]);
});

test('warga aktif dapat mengajukan pinjaman dengan bunga 2 persen', function () {
    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-TEST',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    $this->actingAs($this->warga->user)
        ->post(route('koperasi.store-pinjam'), [
            'amount' => 900000,
            'tenor_months' => 10,
        ])
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_pinjams', [
        'user_id' => $this->warga->user->id,
        'amount' => 900000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 180000, // 2% * 10 bln * 900rb
        'remaining_amount' => 1080000,
        'status' => 'menunggu_persetujuan',
    ]);
});

test('warga aktif tidak bisa mengajukan pinjaman satu juta jika simpanan wajib kurang dari lima puluh ribu', function () {
    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-HIGH-FAIL',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    KoperasiSimpanan::create([
        'user_id' => $this->warga->user->id,
        'type' => 'wajib',
        'amount' => 40000,
        'transaction_date' => now()->toDateString(),
        'status' => 'terverifikasi',
    ]);

    $this->actingAs($this->warga->user)
        ->from(route('koperasi.pinjam'))
        ->post(route('koperasi.store-pinjam'), [
            'amount' => 1000000,
            'tenor_months' => 10,
        ])
        ->assertRedirect(route('koperasi.pinjam'))
        ->assertSessionHas('error', 'Pinjaman Rp 1.000.000 atau lebih hanya bisa diajukan jika simpanan wajib terverifikasi minimal Rp 50.000.');

    $this->assertDatabaseMissing('koperasi_pinjams', [
        'user_id' => $this->warga->user->id,
        'amount' => 1000000,
    ]);
});

test('warga aktif bisa mengajukan pinjaman di atas satu juta jika simpanan wajib minimal lima puluh ribu', function () {
    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-HIGH-PASS',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    KoperasiSimpanan::create([
        'user_id' => $this->warga->user->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->toDateString(),
        'status' => 'terverifikasi',
    ]);

    $this->actingAs($this->warga->user)
        ->post(route('koperasi.store-pinjam'), [
            'amount' => 1500000,
            'tenor_months' => 10,
        ])
        ->assertRedirect(route('koperasi.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_pinjams', [
        'user_id' => $this->warga->user->id,
        'amount' => 1500000,
        'service_fee_amount' => 300000,
        'remaining_amount' => 1800000,
        'status' => 'menunggu_persetujuan',
    ]);
});

test('halaman ajukan pinjaman menampilkan saldo simpanan wajib terverifikasi', function () {
    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-SALDO-WAJIB',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    KoperasiSimpanan::create([
        'user_id' => $this->warga->user->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->toDateString(),
        'status' => 'terverifikasi',
    ]);

    $this->actingAs($this->warga->user)
        ->get(route('koperasi.pinjam'))
        ->assertOk()
        ->assertSee('Simpanan wajib terverifikasi Anda')
        ->assertSee('Rp 50.000')
        ->assertSee('Pinjaman Rp 1.000.000 atau lebih wajib punya simpanan wajib terverifikasi minimal Rp 50.000.');
});

test('warga dapat melihat sisa pinjaman dan riwayat angsuran', function () {
    KoperasiMember::create([
        'user_id' => $this->warga->user->id,
        'member_number' => 'KOP-RIWAYAT',
        'joined_at' => now(),
        'status' => 'aktif',
    ]);

    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->warga->user->id,
        'amount' => 1000000,
        'tenor_months' => 12,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 240000,
        'remaining_amount' => 1120000,
        'status' => 'disetujui',
    ]);

    KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjaman->id,
        'amount' => 120000,
        'paid_at' => now()->toDateString(),
        'status' => 'terverifikasi',
        'verified_at' => now(),
    ]);

    $this->actingAs($this->warga->user)
        ->get(route('koperasi.index'))
        ->assertOk()
        ->assertSee('Sisa Pinjaman')
        ->assertSee('Rp 1.120.000')
        ->assertSee('Sudah Dibayar')
        ->assertSee('Rp 120.000')
        ->assertSee('Riwayat Angsuran Terbaru')
        ->assertSee('Terverifikasi');
});
