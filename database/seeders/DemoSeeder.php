<?php

namespace Database\Seeders;

use App\Models\IuranBulanan;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $roles = Role::whereIn('name', ['admin', 'bendahara', 'sekretaris', 'warga'])
            ->get()
            ->keyBy('name');

        $this->createPengurus($roles);
        $warga = $this->createRumahDanWarga($roles['warga']);
        $this->createIuranDanTagihan($warga);
        $this->createPengeluaran();
        $this->createPengaduan($warga);

        $this->command?->info('Demo data selesai: 35 rumah, 50 warga, tagihan, kas, dan pengaduan dibuat.');
    }

    private function createPengurus($roles): void
    {
        $accounts = [
            ['name' => 'Admin RT', 'email' => 'admin@kas-rt.test', 'role' => 'admin'],
            ['name' => 'Bendahara RT', 'email' => 'bendahara@kas-rt.test', 'role' => 'bendahara'],
            ['name' => 'Sekretaris RT', 'email' => 'sekretaris@kas-rt.test', 'role' => 'sekretaris'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $roles[$account['role']]->id,
                    'email_verified_at' => now(),
                ]
            );
        }
    }

    private function createRumahDanWarga(Role $wargaRole)
    {
        $names = [
            'Budi Santoso', 'Siti Aminah', 'Agus Prasetyo', 'Dewi Lestari', 'Rudi Hartono',
            'Maya Permata', 'Hendra Wijaya', 'Nina Kartika', 'Fajar Nugroho', 'Lina Marlina',
            'Dedi Kurniawan', 'Ratna Sari', 'Yusuf Maulana', 'Indah Pertiwi', 'Taufik Hidayat',
            'Rina Anggraini', 'Arif Setiawan', 'Wulan Safitri', 'Bambang Irawan', 'Fitri Handayani',
            'Joko Susilo', 'Sri Wahyuni', 'Andi Saputra', 'Novi Rahmawati', 'Eko Purnomo',
            'Dian Novita', 'Rizky Ramadhan', 'Putri Maharani', 'Bayu Pratama', 'Citra Ayu',
            'Maman Suherman', 'Nana Mulyana', 'Slamet Riyadi', 'Asep Saepudin', 'Tini Kartini',
            'Galih Firmansyah', 'Mega Puspita', 'Yoga Hermawan', 'Sarah Aulia', 'Teguh Santoso',
            'Ari Wibowo', 'Mila Karmila', 'Reza Fahlevi', 'Anisa Putri', 'Irwan Syah',
            'Nadia Lutfiah', 'Fikri Alamsyah', 'Rossa Amelia', 'Wahyu Saputra', 'Tari Oktaviani',
        ];

        $users = collect();

        for ($i = 1; $i <= 35; $i++) {
            $kode = 'A-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $rumah = Rumah::updateOrCreate(
                ['kode_rumah' => $kode],
                [
                    'alamat' => 'Jl. Melati No. ' . $i,
                    'rt' => '001',
                    'rw' => '002',
                    'status' => 'aktif',
                ]
            );

            $name = $names[$i - 1];
            $user = User::updateOrCreate(
                ['email' => 'warga' . $i . '@kas-rt.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role_id' => $wargaRole->id,
                    'rumah_id' => $rumah->id,
                    'no_kk' => '3174' . str_pad((string) $i, 12, '0', STR_PAD_LEFT),
                    'is_kepala_keluarga' => true,
                    'is_penanggung_jawab_rumah' => true,
                    'jumlah_anggota_keluarga' => fake()->numberBetween(2, 5),
                    'phone' => '0812' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'rt' => '001',
                    'rw' => '002',
                    'email_verified_at' => now(),
                ]
            );

            $rumah->update(['penanggung_jawab_id' => $user->id]);
            $users->push($user);
        }

        for ($i = 36; $i <= 50; $i++) {
            $rumah = Rumah::where('kode_rumah', 'A-' . str_pad((string) ($i - 35), 2, '0', STR_PAD_LEFT))->first();
            $name = $names[$i - 1];

            $users->push(User::updateOrCreate(
                ['email' => 'warga' . $i . '@kas-rt.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role_id' => $wargaRole->id,
                    'rumah_id' => $rumah->id,
                    'no_kk' => '3174' . str_pad((string) $i, 12, '0', STR_PAD_LEFT),
                    'is_kepala_keluarga' => true,
                    'is_penanggung_jawab_rumah' => false,
                    'jumlah_anggota_keluarga' => fake()->numberBetween(1, 4),
                    'phone' => '0813' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'rt' => '001',
                    'rw' => '002',
                    'email_verified_at' => now(),
                ]
            ));
        }

        return $users;
    }

    private function createIuranDanTagihan($warga): void
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $items = [
            ['nama' => 'Iuran Kebersihan', 'jumlah' => 20000, 'keterangan' => 'Operasional kebersihan lingkungan', 'is_wajib' => true],
            ['nama' => 'Iuran Keamanan', 'jumlah' => 15000, 'keterangan' => 'Operasional keamanan lingkungan', 'is_wajib' => true],
            ['nama' => 'Dana Sosial', 'jumlah' => 5000, 'keterangan' => 'Dana sosial warga', 'is_wajib' => false],
        ];

        foreach ($items as $item) {
            IuranBulanan::updateOrCreate(
                ['nama' => $item['nama'], 'bulan' => $bulan, 'tahun' => $tahun],
                $item + ['bulan' => $bulan, 'tahun' => $tahun]
            );
        }

        Tagihan::generateForMonth($bulan, $tahun);

        Rumah::with('penanggungJawab')->where('status', 'aktif')->get()->each(function (Rumah $rumah, int $index) use ($bulan, $tahun) {
            if (! $rumah->penanggungJawab) {
                return;
            }

            $status = match (true) {
                $index < 20 => 'lunas',
                $index < 25 => 'pending_transfer',
                $index < 29 => 'pending_offline',
                default => 'belum_bayar',
            };

            $tagihan = Tagihan::where('rumah_id', $rumah->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('billing_group', 'iuran_rutin')
                ->first();

            if (! $tagihan) {
                return;
            }

            $tagihan->update([
                'status' => $status,
                'payment_method' => $status === 'pending_transfer' ? 'transfer' : ($status === 'pending_offline' ? 'offline' : ($status === 'lunas' ? 'offline' : 'none')),
                'note' => $status === 'pending_offline' ? 'Akan diserahkan ke bendahara.' : null,
                'paid_at' => $status === 'lunas' ? now()->subDays(fake()->numberBetween(1, 12)) : null,
            ]);

            if ($status === 'lunas') {
                KasMasuk::updateOrCreate(
                    ['tagihan_id' => $tagihan->id],
                    [
                        'user_id' => $rumah->penanggungJawab->id,
                        'keterangan' => 'Pembayaran ' . $tagihan->display_title . ' Rumah ' . $rumah->kode_rumah,
                        'jumlah' => $tagihan->total,
                        'tanggal' => $tagihan->paid_at ?? now(),
                    ]
                );
            }
        });
    }

    private function createPengeluaran(): void
    {
        $items = [
            ['keterangan' => 'Pembelian alat kebersihan', 'jumlah' => 175000, 'tanggal' => now()->subDays(10)],
            ['keterangan' => 'Perbaikan lampu jalan', 'jumlah' => 320000, 'tanggal' => now()->subDays(8)],
            ['keterangan' => 'Konsumsi rapat warga', 'jumlah' => 210000, 'tanggal' => now()->subDays(5)],
            ['keterangan' => 'Iuran sampah kolektif', 'jumlah' => 450000, 'tanggal' => now()->subDays(3)],
        ];

        foreach ($items as $item) {
            KasKeluar::updateOrCreate(
                ['keterangan' => $item['keterangan'], 'tanggal' => $item['tanggal']->toDateString()],
                $item
            );
        }
    }

    private function createPengaduan($warga): void
    {
        $items = [
            ['judul' => 'Lampu jalan depan pos redup', 'kategori' => 'Infrastruktur', 'status' => 'pending'],
            ['judul' => 'Jadwal angkut sampah terlambat', 'kategori' => 'Kebersihan', 'status' => 'proses'],
            ['judul' => 'Parkir tamu menghalangi gang', 'kategori' => 'Keamanan', 'status' => 'pending'],
            ['judul' => 'Usulan kerja bakti bulanan', 'kategori' => 'Sosial', 'status' => 'selesai'],
        ];

        foreach ($items as $index => $item) {
            Pengaduan::updateOrCreate(
                ['judul' => $item['judul']],
                [
                    'user_id' => $warga[$index]->id,
                    'kategori' => $item['kategori'],
                    'deskripsi' => 'Data demo untuk kebutuhan presentasi sistem kas RT.',
                    'status' => $item['status'],
                    'tanggapan' => $item['status'] === 'pending' ? null : 'Sudah diterima dan akan ditindaklanjuti pengurus.',
                    'tanggapan_at' => $item['status'] === 'pending' ? null : now()->subDay(),
                ]
            );
        }
    }
}
