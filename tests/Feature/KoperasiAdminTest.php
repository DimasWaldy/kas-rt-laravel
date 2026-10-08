<?php

use App\Models\User;
use App\Models\Role;
use App\Models\KoperasiMember;
use App\Models\KoperasiSimpanan;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiAngsuran;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\Rumah;

use App\Models\Warga;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function buatWargaKoperasiAdmin(?Rt $rt = null) {
    $role = Role::firstOrCreate(['name' => 'warga']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'rt_id' => $rt?->id,
        'status_akun' => 'aktif',
    ]);
    return Warga::create([
        'user_id' => $user->id,
        'nama_lengkap' => $user->name,
        'nik' => str_pad((string) $user->id, 16, '1', STR_PAD_LEFT),
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

use App\Models\Permission;

beforeEach(function () {
    $rw = Rw::create(['name' => 'RW Test Koperasi']);
    $rt = Rt::create(['rw_id' => $rw->id, 'name' => 'RT 01']);

    $bendaharaRole = Role::firstOrCreate(['name' => 'bendahara']);

    $pManage = Permission::firstOrCreate(['name' => 'manage-koperasi']);
    $pApprove = Permission::firstOrCreate(['name' => 'approve-koperasi']);
    $bendaharaRole->permissions()->syncWithoutDetaching([$pManage->id, $pApprove->id]);

    $this->admin = User::factory()->create([
        'role_id' => $bendaharaRole->id,
        'rt_id' => $rt->id,
    ]);

    $this->wargaUser = buatWargaKoperasiAdmin($rt)->user;
});

test('admin dapat melihat dashboard koperasi', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.koperasi.index'))
        ->assertOk()
        ->assertSee('Kelola Koperasi');
});

test('bendahara rt tidak melihat data koperasi warga rt lain dan warga tanpa rt', function () {
    $rtLain = Rt::create(['rw_id' => $this->admin->rt()->value('rw_id'), 'name' => 'RT 02']);
    $wargaRtLain = buatWargaKoperasiAdmin($rtLain)->user;
    $wargaTanpaRt = buatWargaKoperasiAdmin()->user;

    KoperasiMember::create([
        'user_id' => $wargaRtLain->id,
        'member_number' => 'KOP-RTLAIN',
        'joined_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    KoperasiSimpanan::create([
        'user_id' => $wargaRtLain->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    KoperasiSimpanan::create([
        'user_id' => $wargaTanpaRt->id,
        'type' => 'wajib',
        'amount' => 60000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    KoperasiPinjam::create([
        'user_id' => $wargaRtLain->id,
        'amount' => 750000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 90000,
        'remaining_amount' => 840000,
        'status' => 'menunggu_persetujuan',
    ]);

    $pinjamanTanpaRt = KoperasiPinjam::create([
        'user_id' => $wargaTanpaRt->id,
        'amount' => 950000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 114000,
        'remaining_amount' => 1064000,
        'status' => 'menunggu_persetujuan',
    ]);

    KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjamanTanpaRt->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.koperasi.index'))
        ->assertOk()
        ->assertDontSee($wargaRtLain->name)
        ->assertDontSee($wargaTanpaRt->name)
        ->assertDontSee('Rp 750.000')
        ->assertDontSee('Rp 950.000');
});

test('bendahara rt tidak bisa approve simpanan pinjaman dan angsuran milik rt lain atau user tanpa rt', function () {
    $rtLain = Rt::create(['rw_id' => $this->admin->rt()->value('rw_id'), 'name' => 'RT 02']);
    $wargaRtLain = buatWargaKoperasiAdmin($rtLain)->user;
    $wargaTanpaRt = buatWargaKoperasiAdmin()->user;

    $simpananRtLain = KoperasiSimpanan::create([
        'user_id' => $wargaRtLain->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $simpananTanpaRt = KoperasiSimpanan::create([
        'user_id' => $wargaTanpaRt->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $pinjamanRtLain = KoperasiPinjam::create([
        'user_id' => $wargaRtLain->id,
        'amount' => 750000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 90000,
        'remaining_amount' => 840000,
        'status' => 'menunggu_persetujuan',
    ]);

    $pinjamanTanpaRt = KoperasiPinjam::create([
        'user_id' => $wargaTanpaRt->id,
        'amount' => 950000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 114000,
        'remaining_amount' => 1064000,
        'status' => 'menunggu_persetujuan',
    ]);

    $angsuranRtLain = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjamanRtLain->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $angsuranTanpaRt = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjamanTanpaRt->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-simpanan', $simpananRtLain), [
            'status' => 'terverifikasi',
        ])
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-simpanan', $simpananTanpaRt), [
            'status' => 'terverifikasi',
        ])
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjamanRtLain), [
            'status' => 'disetujui',
        ])
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjamanTanpaRt), [
            'status' => 'disetujui',
        ])
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-angsuran', $angsuranRtLain), [
            'status' => 'terverifikasi',
        ])
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-angsuran', $angsuranTanpaRt), [
            'status' => 'terverifikasi',
        ])
        ->assertNotFound();

    expect($simpananRtLain->fresh()->status)->toBe('pending')
        ->and($simpananTanpaRt->fresh()->status)->toBe('pending')
        ->and($pinjamanRtLain->fresh()->status)->toBe('menunggu_persetujuan')
        ->and($pinjamanTanpaRt->fresh()->status)->toBe('menunggu_persetujuan')
        ->and($angsuranRtLain->fresh()->status)->toBe('pending')
        ->and($angsuranTanpaRt->fresh()->status)->toBe('pending');
});

test('bendahara rt tidak bisa melihat bukti koperasi milik rt lain atau user tanpa rt', function () {
    Storage::fake('local');

    $rtLain = Rt::create(['rw_id' => $this->admin->rt()->value('rw_id'), 'name' => 'RT 02']);
    $wargaRtLain = buatWargaKoperasiAdmin($rtLain)->user;
    $wargaTanpaRt = buatWargaKoperasiAdmin()->user;

    $path = UploadedFile::fake()->image('bukti.jpg')->store('koperasi/test', 'local');

    $simpananRtLain = KoperasiSimpanan::create([
        'user_id' => $wargaRtLain->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'proof_path' => $path,
        'status' => 'pending',
    ]);

    $simpananTanpaRt = KoperasiSimpanan::create([
        'user_id' => $wargaTanpaRt->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'proof_path' => $path,
        'status' => 'pending',
    ]);

    $pinjamanRtLain = KoperasiPinjam::create([
        'user_id' => $wargaRtLain->id,
        'amount' => 750000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 90000,
        'remaining_amount' => 840000,
        'proof_path' => $path,
        'status' => 'menunggu_persetujuan',
    ]);

    $pinjamanTanpaRt = KoperasiPinjam::create([
        'user_id' => $wargaTanpaRt->id,
        'amount' => 950000,
        'tenor_months' => 6,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 114000,
        'remaining_amount' => 1064000,
        'proof_path' => $path,
        'status' => 'menunggu_persetujuan',
    ]);

    $angsuranRtLain = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjamanRtLain->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'proof_path' => $path,
        'status' => 'pending',
    ]);

    $angsuranTanpaRt = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjamanTanpaRt->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'proof_path' => $path,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)->get(route('admin.koperasi.simpanan-bukti', $simpananRtLain))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.koperasi.simpanan-bukti', $simpananTanpaRt))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.koperasi.pinjaman-bukti', $pinjamanRtLain))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.koperasi.pinjaman-bukti', $pinjamanTanpaRt))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.koperasi.angsuran-bukti', $angsuranRtLain))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.koperasi.angsuran-bukti', $angsuranTanpaRt))->assertNotFound();
});

test('migration backfill users rt id dari rumah dan rumah diajukan tanpa menimpa rt id lama', function () {
    $rw = Rw::create(['name' => 'RW Backfill']);
    $rtRumah = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Rumah']);
    $rtDiajukan = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Diajukan']);
    $rtExisting = Rt::create(['rw_id' => $rw->id, 'name' => 'RT Existing']);

    $rumah = Rumah::create([
        'kode_rumah' => 'BF-001',
        'alamat' => 'Alamat internal tidak penting',
        'rt_id' => $rtRumah->id,
        'status' => 'aktif',
    ]);

    $rumahDiajukan = Rumah::create([
        'kode_rumah' => 'BF-002',
        'alamat' => 'Alamat internal tidak penting',
        'rt_id' => $rtDiajukan->id,
        'status' => 'aktif',
    ]);

    $userDariRumah = User::factory()->create(['rumah_id' => $rumah->id, 'rt_id' => null]);
    $userDariWarga = User::factory()->create(['rumah_id' => null, 'rt_id' => null]);
    $userTidakDitimpa = User::factory()->create(['rumah_id' => $rumah->id, 'rt_id' => $rtExisting->id]);

    Warga::create([
        'user_id' => $userDariWarga->id,
        'nama_lengkap' => $userDariWarga->name,
        'rumah_diajukan_id' => $rumahDiajukan->id,
        'status_verifikasi' => 'pending',
    ]);

    $migration = include database_path('migrations/2026_10_08_000001_backfill_users_rt_id_from_rumah.php');
    $migration->up();
    $migration->up();

    expect($userDariRumah->fresh()->rt_id)->toBe($rtRumah->id)
        ->and($userDariWarga->fresh()->rt_id)->toBe($rtDiajukan->id)
        ->and($userTidakDitimpa->fresh()->rt_id)->toBe($rtExisting->id);
});

test('admin dapat memverifikasi simpanan', function () {
    $simpanan = KoperasiSimpanan::create([
        'user_id' => $this->wargaUser->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-simpanan', $simpanan->id), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_simpanans', [
        'id' => $simpanan->id,
        'status' => 'terverifikasi',
        'verified_by' => $this->admin->id,
    ]);
});

test('admin dapat melihat bukti simpanan koperasi lewat route privat', function () {
    Storage::fake('local');

    $path = UploadedFile::fake()->image('bukti-simpanan.jpg')->store('koperasi/simpanan', 'local');

    $simpanan = KoperasiSimpanan::create([
        'user_id' => $this->wargaUser->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'proof_path' => $path,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.koperasi.simpanan-bukti', $simpanan))
        ->assertOk();
});

test('admin dapat menyetujui pinjaman', function () {
    Storage::fake('local');

    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'menunggu_persetujuan',
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->image('bukti_transfer.jpg');

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjaman->id), [
            'status' => 'disetujui',
            'proof_file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_pinjams', [
        'id' => $pinjaman->id,
        'status' => 'disetujui',
        'approved_by' => $this->admin->id,
    ]);

    $pinjaman->refresh();
    expect($pinjaman->proof_path)->not->toBeNull();
    Storage::disk('local')->assertExists($pinjaman->proof_path);
});

test('admin dapat memverifikasi angsuran dan sisa pinjaman berkurang', function () {
    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'disetujui',
    ]);

    $angsuran = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjaman->id,
        'amount' => 120000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-angsuran', $angsuran->id), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('koperasi_angsurans', [
        'id' => $angsuran->id,
        'status' => 'terverifikasi',
    ]);

    $this->assertDatabaseHas('koperasi_pinjams', [
        'id' => $pinjaman->id,
        'remaining_amount' => 1200000 - 120000,
    ]);
});

test('angsuran yang sudah diproses tidak bisa diverifikasi ulang', function () {
    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'disetujui',
    ]);

    $angsuran = KoperasiAngsuran::create([
        'koperasi_pinjam_id' => $pinjaman->id,
        'amount' => 120000,
        'paid_at' => now()->format('Y-m-d'),
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.koperasi.approve-angsuran', $angsuran), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($this->admin)
        ->from(route('admin.koperasi.index'))
        ->patch(route('admin.koperasi.approve-angsuran', $angsuran), [
            'status' => 'terverifikasi',
        ])
        ->assertRedirect(route('admin.koperasi.index'))
        ->assertSessionHasErrors('status');

    expect($pinjaman->fresh()->remaining_amount)->toBe(1080000)
        ->and($angsuran->fresh()->status)->toBe('terverifikasi');
});

test('pinjaman yang sudah diproses tidak bisa diapprove ulang', function () {
    $pinjaman = KoperasiPinjam::create([
        'user_id' => $this->wargaUser->id,
        'amount' => 1000000,
        'tenor_months' => 10,
        'service_fee_percentage' => 2.00,
        'service_fee_amount' => 200000,
        'remaining_amount' => 1200000,
        'status' => 'disetujui',
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.koperasi.index'))
        ->patch(route('admin.koperasi.approve-pinjaman', $pinjaman), [
            'status' => 'ditolak',
            'rejected_reason' => 'Dokumen tidak valid.',
        ])
        ->assertRedirect(route('admin.koperasi.index'))
        ->assertSessionHasErrors('status');

    expect($pinjaman->fresh()->status)->toBe('disetujui');
});

test('simpanan yang sudah diproses tidak bisa diverifikasi ulang', function () {
    $simpanan = KoperasiSimpanan::create([
        'user_id' => $this->wargaUser->id,
        'type' => 'wajib',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'status' => 'terverifikasi',
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.koperasi.index'))
        ->patch(route('admin.koperasi.approve-simpanan', $simpanan), [
            'status' => 'ditolak',
            'rejected_reason' => 'Bukti tidak valid.',
        ])
        ->assertRedirect(route('admin.koperasi.index'))
        ->assertSessionHasErrors('status');

    expect($simpanan->fresh()->status)->toBe('terverifikasi');
});
