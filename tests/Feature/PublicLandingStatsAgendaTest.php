<?php

use App\Models\Balita;
use App\Models\JenisSampah;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\SetoranSampah;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-22 10:00:00', 'Asia/Jakarta'));
    $this->seed(RoleAndPermissionSeeder::class);

    $this->rwAktif = Rw::create([
        'name' => 'RW 01 Aktif',
        'is_active' => true,
    ]);
    $this->rtSatu = Rt::create([
        'rw_id' => $this->rwAktif->id,
        'name' => 'RT 01',
        'is_active' => true,
    ]);
    $this->rtDua = Rt::create([
        'rw_id' => $this->rwAktif->id,
        'name' => 'RT 02',
        'is_active' => true,
    ]);

    $this->rwNonaktif = Rw::create([
        'name' => 'RW 99 Nonaktif',
        'is_active' => false,
    ]);
    $this->rtNonaktif = Rt::create([
        'rw_id' => $this->rwNonaktif->id,
        'name' => 'RT 99',
        'is_active' => false,
    ]);

    $wargaRoleId = Role::where('name', 'warga')->value('id');
    $pengurusRoleId = Role::where('name', 'ketua_rw')->value('id');

    $this->pengurus = User::factory()->create([
        'role_id' => $pengurusRoleId,
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaAktif = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtSatu->id,
    ]);

    $this->wargaNonaktif = User::factory()->create([
        'role_id' => $wargaRoleId,
        'rt_id' => $this->rtNonaktif->id,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('landing page menampilkan statistik nyata rw, transparansi kas agregat, dan agenda kegiatan mendatang tanpa membocorkan privasi', function () {
    $rumah = Rumah::create(['kode_rumah' => 'A-01', 'rt_id' => $this->rtSatu->id]);
    $kk = KartuKeluarga::create([
        'no_kk' => '3201010101010001',
        'rumah_id' => $rumah->id,
        'nama_kepala_keluarga' => 'Budi Santoso',
    ]);

    Warga::create([
        'user_id' => $this->wargaAktif->id,
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '3201010101010001',
        'kartu_keluarga_id' => $kk->id,
        'status_dalam_kk' => 'kepala_keluarga',
        'status_verifikasi' => 'terverifikasi',
    ]);
    Warga::create([
        'user_id' => $this->wargaNonaktif->id,
        'nama_lengkap' => 'Warga Wilayah Mati',
        'nik' => '3201010101010002',
        'kartu_keluarga_id' => $kk->id,
        'status_dalam_kk' => 'anggota',
        'status_verifikasi' => 'terverifikasi',
    ]);

    // Transaksi kas aktif (bulan lalu & bulan ini) + transaksi RT nonaktif yang harus dikecualikan
    KasMasuk::create([
        'user_id' => $this->wargaAktif->id,
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Setoran Rahasia Pribadi Bulan Lalu',
        'jumlah' => 500000,
        'tanggal' => '2026-05-15',
    ]);
    KasMasuk::create([
        'user_id' => $this->wargaAktif->id,
        'rt_id' => $this->rtDua->id,
        'keterangan' => 'Iuran Rahasia Bulan Juni',
        'jumlah' => 300000,
        'tanggal' => '2026-06-10',
    ]);
    KasKeluar::create([
        'rt_id' => $this->rtSatu->id,
        'keterangan' => 'Beli Sapu Rahasia',
        'jumlah' => 125000,
        'tanggal' => '2026-06-12',
    ]);
    KasMasuk::create([
        'user_id' => $this->wargaNonaktif->id,
        'rt_id' => $this->rtNonaktif->id,
        'keterangan' => 'Kas RW Mati',
        'jumlah' => 9999000,
        'tanggal' => '2026-06-10',
    ]);

    // Bank Sampah (hanya yang diverifikasi di RW aktif yang dihitung)
    $jenis = JenisSampah::create([
        'rw_id' => $this->rwAktif->id,
        'nama' => 'Plastik',
        'satuan' => 'kg',
        'harga_per_satuan' => 3000,
        'is_active' => true,
    ]);
    SetoranSampah::create([
        'rw_id' => $this->rwAktif->id,
        'warga_id' => $this->wargaAktif->id,
        'petugas_id' => $this->pengurus->id,
        'jenis_sampah_id' => $jenis->id,
        'estimasi_berat' => 12.5,
        'berat_aktual' => 12.5,
        'nilai' => 37500,
        'status' => 'diverifikasi',
        'metode_setor' => 'langsung_petugas',
        'tanggal_setor' => '2026-06-15',
    ]);
    SetoranSampah::create([
        'rw_id' => $this->rwAktif->id,
        'warga_id' => $this->wargaAktif->id,
        'jenis_sampah_id' => $jenis->id,
        'estimasi_berat' => 50.0,
        'berat_aktual' => 50.0,
        'nilai' => 0,
        'status' => 'menunggu',
        'metode_setor' => 'setor_mandiri',
        'tanggal_setor' => '2026-06-20',
    ]);

    // Posyandu
    Balita::create([
        'rw_id' => $this->rwAktif->id,
        'rt_id' => $this->rtSatu->id,
        'orang_tua_id' => $this->wargaAktif->id,
        'nama' => 'Balita Rahasia',
        'jenis_kelamin' => 'laki_laki',
        'tanggal_lahir' => '2025-05-01',
        'is_active' => true,
    ]);

    // Agenda kegiatan
    $agendaMendatang = Kegiatan::create([
        'rw_id' => $this->rwAktif->id,
        'rt_id' => null,
        'created_by' => $this->pengurus->id,
        'nama' => 'Kerja Bakti Bersih Saluran',
        'deskripsi' => 'Kerja bakti rutin membersihkan saluran air lingkungan RW.',
        'tanggal_mulai' => now()->addDays(3)->setTime(8, 0),
        'tanggal_selesai' => now()->addDays(3)->setTime(11, 0),
        'lokasi' => 'Balai RW 01',
        'status' => 'akan_datang',
    ]);
    KegiatanHadir::create([
        'kegiatan_id' => $agendaMendatang->id,
        'user_id' => $this->wargaAktif->id,
        'confirmed_at' => now(),
    ]);

    Kegiatan::create([
        'rw_id' => $this->rwAktif->id,
        'created_by' => $this->pengurus->id,
        'nama' => 'Agenda Batal Jangan Tampil',
        'tanggal_mulai' => now()->addDays(4),
        'status' => 'dibatalkan',
    ]);
    Kegiatan::create([
        'rw_id' => $this->rwAktif->id,
        'created_by' => $this->pengurus->id,
        'nama' => 'Agenda Lampau Sudah Selesai',
        'tanggal_mulai' => now()->subDays(5),
        'tanggal_selesai' => now()->subDays(5)->addHours(2),
        'status' => 'selesai',
    ]);
    Kegiatan::create([
        'rw_id' => $this->rwNonaktif->id,
        'created_by' => $this->pengurus->id,
        'nama' => 'Agenda RW Nonaktif',
        'tanggal_mulai' => now()->addDays(2),
        'status' => 'akan_datang',
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('2 RT')
        ->assertSee('1 Warga Terverifikasi')
        ->assertSee('1 Agenda')
        ->assertSee('Rp 675.000')
        ->assertSee('+Rp 300.000')
        ->assertSee('Pengeluaran: Rp 125.000')
        ->assertSee('12,5 kg')
        ->assertSee('1 setoran terverifikasi')
        ->assertSee('1 Balita')
        ->assertSee('Kerja Bakti Bersih Saluran')
        ->assertSee('Balai RW 01')
        ->assertSee('1 warga konfirmasi hadir')
        ->assertDontSee('Setoran Rahasia Pribadi Bulan Lalu')
        ->assertDontSee('Iuran Rahasia Bulan Juni')
        ->assertDontSee('Beli Sapu Rahasia')
        ->assertDontSee('Balita Rahasia')
        ->assertDontSee('Budi Santoso')
        ->assertDontSee('Agenda Batal Jangan Tampil')
        ->assertDontSee('Agenda Lampau Sudah Selesai')
        ->assertDontSee('Agenda RW Nonaktif');
});

test('landing page menampilkan empty state agenda saat belum ada kegiatan terjadwal', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Belum Ada Agenda Kegiatan Mendatang');
});
