<?php

use App\Models\Aset;
use App\Models\PeminjamanAset;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Notifications\PeminjamanDiajukan;
use App\Notifications\PeminjamanDisetujui;
use App\Notifications\PeminjamanDitolak;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-17 09:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rw = Rw::create([
        'name' => 'RW Test',
        'address' => 'Jl. Notifikasi Aset No. 5',
        'is_active' => true,
    ]);

    $this->rtSatu = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);

    $this->rtDua = Rt::create([
        'rw_id' => $this->rw->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $roleId = fn (string $name) => Role::where('name', $name)->value('id');

    $this->pengurusRtSatu = User::factory()->create([
        'role_id' => $roleId('ketua_rt'),
        'rt_id' => $this->rtSatu->id,
        'name' => 'Ketua RT Satu',
    ]);

    $this->pengurusRtDua = User::factory()->create([
        'role_id' => $roleId('ketua_rt'),
        'rt_id' => $this->rtDua->id,
        'name' => 'Ketua RT Dua',
    ]);

    $this->warga = User::factory()->create([
        'role_id' => $roleId('warga'),
        'rt_id' => $this->rtSatu->id,
        'name' => 'Warga Peminjam',
    ]);

    $this->aset = Aset::create([
        'rt_id' => $this->rtSatu->id,
        'rw_id' => $this->rw->id,
        'scope' => 'rt',
        'nama' => 'Tenda Notifikasi',
        'kategori' => 'tenda_dan_terpal',
        'jumlah_total' => 3,
        'kondisi' => 'baik',
        'lokasi_penyimpanan' => 'Gudang RT',
        'is_active' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('pengajuan peminjaman mengirim notifikasi ke pengurus rt', function () {
    $this->actingAs($this->warga)
        ->post(route('peminjaman-aset.store'), [
            'aset_id' => $this->aset->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'keperluan' => 'Kegiatan warga',
            'jumlah_dipinjam' => 1,
        ])
        ->assertRedirect(route('peminjaman-aset.index'));

    $notification = $this->pengurusRtSatu->fresh()->unreadNotifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe(PeminjamanDiajukan::class)
        ->and($notification->data['aset_nama'])->toBe('Tenda Notifikasi')
        ->and($notification->data['pemohon_nama'])->toBe('Warga Peminjam')
        ->and($notification->data['keperluan'])->toBe('Kegiatan warga');
});

test('persetujuan peminjaman mengirim notifikasi ke pemohon', function () {
    $peminjaman = PeminjamanAset::create([
        'aset_id' => $this->aset->id,
        'pemohon_id' => $this->warga->id,
        'tanggal_mulai' => now()->addDays(2)->toDateString(),
        'tanggal_selesai' => now()->addDays(3)->toDateString(),
        'keperluan' => 'Kegiatan warga',
        'jumlah_dipinjam' => 1,
        'status' => 'diajukan',
    ]);

    $this->actingAs($this->pengurusRtSatu)
        ->patch(route('peminjaman-aset.setujui', $peminjaman), [
            'catatan_pengurus' => 'Silakan diambil pagi hari.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $notification = $this->warga->fresh()->unreadNotifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe(PeminjamanDisetujui::class)
        ->and($notification->data['aset_nama'])->toBe('Tenda Notifikasi')
        ->and($notification->data['catatan'])->toBe('Silakan diambil pagi hari.');
});

test('penolakan peminjaman mengirim notifikasi ke pemohon', function () {
    $peminjaman = PeminjamanAset::create([
        'aset_id' => $this->aset->id,
        'pemohon_id' => $this->warga->id,
        'tanggal_mulai' => now()->addDays(2)->toDateString(),
        'tanggal_selesai' => now()->addDays(3)->toDateString(),
        'keperluan' => 'Kegiatan warga',
        'jumlah_dipinjam' => 1,
        'status' => 'diajukan',
    ]);

    $this->actingAs($this->pengurusRtSatu)
        ->patch(route('peminjaman-aset.tolak', $peminjaman), [
            'catatan_pengurus' => 'Aset dipakai untuk agenda RT.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $notification = $this->warga->fresh()->unreadNotifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe(PeminjamanDitolak::class)
        ->and($notification->data['aset_nama'])->toBe('Tenda Notifikasi')
        ->and($notification->data['alasan'])->toBe('Aset dipakai untuk agenda RT.');
});

test('pengurus rt lain tidak dapat notifikasi peminjaman rt ini', function () {
    $this->actingAs($this->warga)
        ->post(route('peminjaman-aset.store'), [
            'aset_id' => $this->aset->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'keperluan' => 'Kegiatan warga',
            'jumlah_dipinjam' => 1,
        ])
        ->assertRedirect(route('peminjaman-aset.index'));

    expect($this->pengurusRtSatu->fresh()->unreadNotifications()->count())->toBe(1)
        ->and($this->pengurusRtDua->fresh()->unreadNotifications()->count())->toBe(0);
});

test('warga dapat melihat bell dan notifikasi miliknya', function () {
    $peminjaman = PeminjamanAset::create([
        'aset_id' => $this->aset->id,
        'pemohon_id' => $this->warga->id,
        'tanggal_mulai' => now()->addDays(2)->toDateString(),
        'tanggal_selesai' => now()->addDays(3)->toDateString(),
        'keperluan' => 'Kegiatan warga',
        'jumlah_dipinjam' => 1,
        'status' => 'disetujui',
        'catatan_pengurus' => 'Disetujui untuk dipakai.',
    ]);

    $this->warga->notify(new PeminjamanDisetujui($peminjaman));

    $this->actingAs($this->warga)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('fa-bell', false)
        ->assertSee('1')
        ->assertSee('Peminjaman Tenda Notifikasi disetujui.');
});
