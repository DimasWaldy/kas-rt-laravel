<?php

use App\Models\IuranBulanan;
use App\Models\KasMasuk;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('core uas demo flow works end to end inside one rt', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $rw = Rw::create([
        'name' => 'RW Demo',
        'is_active' => true,
    ]);

    $rt = Rt::create([
        'rw_id' => $rw->id,
        'name' => 'RT Demo',
        'is_active' => true,
    ]);

    $bendahara = User::factory()->create([
        'role_id' => Role::where('name', 'bendahara_rt')->value('id'),
        'rt_id' => $rt->id,
    ]);

    $sekretaris = User::factory()->create([
        'role_id' => Role::where('name', 'sekretaris_rt')->value('id'),
        'rt_id' => $rt->id,
    ]);

    $warga = User::factory()->create([
        'role_id' => Role::where('name', 'warga')->value('id'),
        'rt_id' => $rt->id,
        'is_penanggung_jawab_rumah' => true,
    ]);

    $rumah = Rumah::create([
        'kode_rumah' => 'DEMO-01',
        'rt_id' => $rt->id,
        'penanggung_jawab_id' => $warga->id,
        'status' => 'aktif',
    ]);

    $warga->update(['rumah_id' => $rumah->id]);

    IuranBulanan::create([
        'nama' => 'Iuran Kebersihan',
        'jumlah' => 30000,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'is_wajib' => true,
    ]);

    Tagihan::generate(now()->month, now()->year, $bendahara);

    $tagihan = Tagihan::where('rumah_id', $rumah->id)->firstOrFail();

    expect($tagihan->rt_id)->toBe($rt->id);
    expect($tagihan->status)->toBe('belum_bayar');

    $this->actingAs($warga)
        ->post(route('tagihan.pay'), [
            'tagihan_id' => $tagihan->id,
            'payment_method' => 'offline',
            'note' => 'Diserahkan langsung kepada bendahara RT.',
        ])
        ->assertRedirect(route('tagihan.index'));

    expect($tagihan->fresh()->status)->toBe('pending_offline');
    expect($bendahara->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($bendahara)
        ->post(route('tagihan.confirm'), [
            'tagihan_id' => $tagihan->id,
            'status' => 'lunas',
            'verification_note' => 'Pembayaran tunai sudah diterima.',
        ])
        ->assertRedirect(route('tagihan.admin'));

    $kasMasuk = KasMasuk::where('tagihan_id', $tagihan->id)->firstOrFail();

    expect($tagihan->fresh()->status)->toBe('lunas');
    expect($kasMasuk->rt_id)->toBe($rt->id);
    expect((int) $kasMasuk->jumlah)->toBe(30000);

    $this->actingAs($warga)
        ->post(route('pengaduan.store'), [
            '_form' => 'pengaduan',
            'judul' => 'Lampu jalan mati',
            'kategori' => 'Keamanan',
            'deskripsi' => 'Lampu jalan dekat rumah mati sejak semalam.',
        ])
        ->assertRedirect('/pengaduan');

    $pengaduan = Pengaduan::where('user_id', $warga->id)->firstOrFail();

    $this->actingAs($sekretaris)
        ->patch(route('pengaduan.status', $pengaduan), [
            'status' => 'proses',
            'tanggapan' => 'Petugas sedang mengecek lampu tersebut.',
        ])
        ->assertRedirect(route('pengaduan.show', $pengaduan));

    expect($pengaduan->fresh()->status)->toBe('proses');
    expect($pengaduan->fresh()->tanggapan_oleh)->toBe($sekretaris->id);
});
