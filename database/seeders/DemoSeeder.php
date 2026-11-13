<?php

namespace Database\Seeders;

use App\Http\Controllers\Auth\DemoLoginController;
use App\Models\Aset;
use App\Models\Balita;
use App\Models\Fasilitas;
use App\Models\HadiahSampah;
use App\Models\IuranBulanan;
use App\Models\IuranKhusus;
use App\Models\JamOperasionalUmkm;
use App\Models\JenisSampah;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Kegiatan;
use App\Models\KegiatanHadir;
use App\Models\KoperasiAngsuran;
use App\Models\KoperasiMember;
use App\Models\KoperasiPinjam;
use App\Models\KoperasiSimpanan;
use App\Models\PemeriksaanPosyandu;
use App\Models\PeminjamanAset;
use App\Models\PenarikanSampah;
use App\Models\Pengaduan;
use App\Models\PengaduanFasilitas;
use App\Models\PenjualanSampah;
use App\Models\PenukaranHadiah;
use App\Models\ProdukUmkm;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rumah;
use App\Models\Rw;
use App\Models\SaldoSampah;
use App\Models\SetoranSampah;
use App\Models\Surat;
use App\Models\Tagihan;
use App\Models\TransaksiSampah;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private Collection $roles;

    private Collection $rts;

    private Rw $rw;

    private User $superAdmin;

    private User $admin;

    private User $bendahara;

    private User $sekretaris;

    private User $ketuaRt;

    private User $ketuaRw;

    private User $petugasBankSampah;

    private User $petugasPosyandu;

    private Collection $wargaUsers;

    private Collection $rumahs;

    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            RoleAndPermissionSeeder::class,
            WhoGrowthStandardSeeder::class,
        ]);

        $this->roles = Role::all()->keyBy('name');
        $this->rw = Rw::where('name', 'RW 05')->firstOrFail();
        $this->rts = Rt::where('rw_id', $this->rw->id)->orderBy('name')->get();
        $this->wargaUsers = collect();
        $this->rumahs = collect();

        $this->createPengurus();
        $this->createRumahDanWarga();
        $this->createIuranTagihanDanKas();
        $this->createPengaduanDanSurat();
        $this->createKegiatan();
        $this->createAsetDanFasilitas();
        $this->createBankSampah();
        $this->createUmkm();
        $this->createKoperasi();
        $this->createPosyandu();

        $this->command?->info('');
        $this->command?->info('Demo Smart RW siap untuk recording.');
        $this->command?->info('Login admin      : admin@smartrw.test / password');
        $this->command?->info('Login superadmin : superadmin@smartrw.test / password');
        $this->command?->info('Login warga (30) : 30 akun warga demo (14 teman + 16 warga random, default: Yazid)');
        $this->command?->info('Login warga baru : register manual di /register untuk demo verifikasi.');
    }

    private function createPengurus(): void
    {
        $accounts = [
            'superAdmin' => ['Super Admin Smart RW', 'superadmin@smartrw.test', 'super_admin', null],
            'admin' => ['Admin Demo Smart RW', 'admin@smartrw.test', 'admin', null],
            'ketuaRw' => ['Ketua RW 05 Demo', 'ketua.rw@smartrw.test', 'ketua_rw', null],
            'bendahara' => ['Bendahara RT 01 Demo', 'bendahara.rt@smartrw.test', 'bendahara_rt', 'RT 01'],
            'sekretaris' => ['Sekretaris RT 01 Demo', 'sekretaris.rt@smartrw.test', 'sekretaris_rt', 'RT 01'],
            'ketuaRt' => ['Ketua RT 01 Demo', 'ketua.rt@smartrw.test', 'ketua_rt', 'RT 01'],
            'petugasBankSampah' => ['Petugas Bank Sampah RW', 'bank.sampah@smartrw.test', 'petugas_bank_sampah', null],
            'petugasPosyandu' => ['Kader Posyandu RW', 'posyandu@smartrw.test', 'petugas_posyandu', null],
        ];

        foreach ($accounts as $property => [$name, $email, $role, $rtName]) {
            $rtId = $rtName ? $this->rts->firstWhere('name', $rtName)?->id : null;

            $this->{$property} = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'role_id' => $this->roles[$role]->id,
                    'rt_id' => $rtId,
                    'phone' => $this->phoneFor('0899', count(User::all()) + 1),
                    'status_akun' => 'aktif',
                    'email_verified_at' => now(),
                ]
            );
        }
    }

    private function createRumahDanWarga(): void
    {
        $primary30 = DemoLoginController::DEMO_WARGA;

        $names31Plus = [
            'Rina Anggraini', 'Arif Setiawan', 'Wulan Safitri', 'Bambang Irawan', 'Fitri Handayani',
            'Joko Susilo', 'Sri Wahyuni', 'Andi Saputra', 'Novi Rahmawati', 'Eko Purnomo',
            'Dian Novita', 'Rizky Ramadhan', 'Putri Maharani', 'Bayu Pratama', 'Citra Ayu',
            'Maman Suherman', 'Nana Mulyana', 'Slamet Riyadi', 'Asep Saepudin', 'Tini Kartini',
            'Galih Firmansyah', 'Mega Puspita', 'Yoga Hermawan', 'Sarah Aulia', 'Teguh Santoso',
            'Ari Wibowo', 'Mila Karmila', 'Reza Fahlevi', 'Anisa Putri', 'Irwan Syah',
            'Nadia Lutfiah', 'Fikri Alamsyah', 'Rossa Amelia', 'Wahyu Saputra', 'Tari Oktaviani',
            'Rangga Pratama', 'Dinda Safira', 'Raka Aditya', 'Aulia Rahman', 'Farhan Akbar',
            'Mei Anggraini', 'Reno Firmansyah', 'Salsa Nuraini', 'Dimas Mahendra', 'Laras Wening',
            'Ilham Fauzi', 'Nabila Zahra', 'Rian Maulana', 'Vina Amalia', 'Bagas Kurnia',
            'Cahya Putri', 'Gilang Ramadhan', 'Mira Lesmana', 'Rafi Nugraha', 'Ayu Safitri',
            'Damar Nugraha', 'Nisa Fadilah', 'Rizal Ramadhan', 'Tasya Kamila', 'Iqbal Maulana',
            'Siska Wulandari', 'Hafiz Pratama', 'Tiara Permata', 'Reyhan Ardiansyah', 'Mutiara Sari',
        ];

        $counter = 0;
        $activeWargaCount = 0;
        $mainWarga = null;

        foreach ($this->rts as $rtIndex => $rt) {
            foreach (range(1, 12) as $houseNumber) {
                $counter++;
                $kode = sprintf('%s-%02d', chr(65 + $rtIndex), $houseNumber);

                $rumah = Rumah::updateOrCreate(
                    ['kode_rumah' => $kode],
                    [
                        'alamat' => sprintf('Jl. Melati %s No. %d', $rt->name, $houseNumber),
                        'rt' => str_pad((string) ($rtIndex + 1), 3, '0', STR_PAD_LEFT),
                        'rw' => '005',
                        'rt_id' => $rt->id,
                        'status' => $houseNumber % 12 === 0 ? 'kosong' : 'aktif',
                    ]
                );

                $this->rumahs->push($rumah);

                if ($rumah->status !== 'aktif') {
                    continue;
                }

                if ($activeWargaCount < count($primary30)) {
                    $demoInfo = $primary30[$activeWargaCount];
                    $wargaName = $demoInfo['name'];
                    $wargaEmail = $demoInfo['email'];
                } else {
                    $extraIdx = $activeWargaCount - count($primary30);
                    $wargaName = $names31Plus[$extraIdx % count($names31Plus)];
                    $wargaEmail = sprintf('warga%02d@smartrw.test', $activeWargaCount + 1);
                }
                $activeWargaCount++;

                $kepala = $this->createWargaUser(
                    name: $wargaName,
                    email: $wargaEmail,
                    rumah: $rumah,
                    rt: $rt,
                    index: $activeWargaCount,
                    kepalaKeluarga: true
                );

                $rumah->update(['penanggung_jawab_id' => $kepala->id]);
                $this->wargaUsers->push($kepala);
                $mainWarga ??= $kepala;

                if ($houseNumber % 3 === 0) {
                    $anggotaIndex = $counter + 100;
                    $anggota = $this->createWargaUser(
                        name: $names31Plus[($anggotaIndex - 1) % count($names31Plus)],
                        email: sprintf('anggota%02d@smartrw.test', $counter),
                        rumah: $rumah,
                        rt: $rt,
                        index: $anggotaIndex,
                        kepalaKeluarga: false
                    );

                    $this->wargaUsers->push($anggota);
                }
            }
        }

        if ($mainWarga) {
            $mainWarga->update(['email' => 'warga.demo@smartrw.test']);
        }

        $this->createPendingWarga();
    }

    private function createWargaUser(string $name, string $email, Rumah $rumah, Rt $rt, int $index, bool $kepalaKeluarga): User
    {
        $kk = KartuKeluarga::firstOrCreate(
            ['no_kk' => sprintf('327301%06d%04d', (($index * 73) % 900000) + 100000, $kepalaKeluarga ? $index : $index - 100)],
            [
                'rumah_id' => $rumah->id,
                'nama_kepala_keluarga' => $kepalaKeluarga ? $name : ($rumah->penanggungJawab?->name ?? $name),
            ]
        );

        if (! $kk->rumah_id) {
            $kk->update(['rumah_id' => $rumah->id]);
        }

        $phone = sprintf('08%s%08d',
            ['12', '13', '21', '52', '77', '81', '96'][$index % 7],
            (($index * 1337) % 90000000) + 10000000
        );

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'role_id' => $this->roles['warga']->id,
                'rumah_id' => $rumah->id,
                'rt_id' => $rt->id,
                'is_penanggung_jawab_rumah' => $kepalaKeluarga,
                'phone' => $phone,
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        $femaleNames = [
            'Nadia Maharani', 'Siti Aminah', 'Dewi Lestari', 'Maya Permata', 'Nina Kartika',
            'Lina Marlina', 'Ratna Sari', 'Indah Pertiwi', 'Rina Anggraini', 'Wulan Safitri',
            'Fitri Handayani', 'Sri Wahyuni', 'Novi Rahmawati', 'Dian Novita', 'Putri Maharani',
            'Citra Ayu', 'Tini Kartini', 'Mega Puspita', 'Sarah Aulia', 'Mila Karmila',
            'Anisa Putri', 'Nadia Lutfiah', 'Rossa Amelia', 'Tari Oktaviani', 'Dinda Safira',
            'Mei Anggraini', 'Salsa Nuraini', 'Laras Wening', 'Nabila Zahra', 'Vina Amalia',
            'Cahya Putri', 'Mira Lesmana', 'Ayu Safitri', 'Nisa Fadilah', 'Tasya Kamila',
            'Siska Wulandari', 'Tiara Permata', 'Mutiara Sari', 'Rara Puspita', 'Niken Aprilia',
            'Cindy Claudia', 'Della Kartika', 'Melly Oktavia', 'Shinta Maharani',
        ];

        $gender = in_array($name, $femaleNames, true) ? 'P' : 'L';
        $birthYear = 1972 + ($index % 30);
        $birthMonth = ($index % 12) + 1;
        $birthDay = ($index % 28) + 1;
        $tanggalLahir = sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay);

        $nikDay = $gender === 'P' ? $birthDay + 40 : $birthDay;
        $nik = sprintf('3273%02d%02d%02d%02d%04d',
            ($index % 30) + 1,
            $nikDay,
            $birthMonth,
            $birthYear % 100,
            ($index % 9000) + 1000
        );

        Warga::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kartu_keluarga_id' => $kk->id,
                'nik' => $nik,
                'nama_lengkap' => $name,
                'status_dalam_kk' => $kepalaKeluarga ? 'kepala_keluarga' : 'anggota',
                'tanggal_lahir' => $tanggalLahir,
                'jenis_kelamin' => $gender,
                'status_verifikasi' => 'terverifikasi',
                'metode_verifikasi' => 'tatap_muka',
                'diverifikasi_oleh' => $this->ketuaRt->id,
                'diverifikasi_at' => now()->subDays(($index % 60) + 10),
                'catatan_verifikasi' => 'Data demo warga terverifikasi untuk pengujian sistem.',
            ]
        );

        return $user;
    }

    private function createPendingWarga(): void
    {
        $rt = $this->rts->firstWhere('name', 'RT 01');
        $rumah = $this->rumahs->where('rt_id', $rt->id)->where('status', 'aktif')->values()->get(4);

        foreach ([
            ['Calon Warga Baru', 'calon.warga@smartrw.test'],
            ['Rizki Pendaftar', 'rizki.pendaftar@smartrw.test'],
            ['Mawar Melati', 'mawar.melati@smartrw.test'],
        ] as $i => [$name, $email]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'role_id' => $this->roles['warga']->id,
                    'rumah_id' => null,
                    'rt_id' => $i === 0 ? $rt->id : null,
                    'phone' => $this->phoneFor('0821', $i + 1),
                    'status_akun' => 'pending_verifikasi',
                    'email_verified_at' => now(),
                ]
            );

            Warga::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $name,
                    'nik' => $this->number16(3273990000000000, $i + 1),
                    'status_dalam_kk' => $i === 2 ? 'anggota' : 'kepala_keluarga',
                    'status_verifikasi' => 'pending',
                    'rumah_diajukan_id' => $rumah?->id,
                    'rumah_diajukan' => $rumah?->label,
                    'catatan_verifikasi' => null,
                ]
            );
        }
    }

    private function createIuranTagihanDanKas(): void
    {
        foreach ([now()->subMonth(), now()] as $period) {
            $items = [
                ['nama' => 'Iuran Kebersihan', 'jumlah' => 20000, 'keterangan' => 'Operasional kebersihan lingkungan', 'is_wajib' => true],
                ['nama' => 'Iuran Keamanan', 'jumlah' => 15000, 'keterangan' => 'Operasional ronda dan keamanan', 'is_wajib' => true],
                ['nama' => 'Dana Sosial', 'jumlah' => 5000, 'keterangan' => 'Dana sosial warga', 'is_wajib' => false],
            ];

            foreach ($items as $item) {
                IuranBulanan::updateOrCreate(
                    ['nama' => $item['nama'], 'bulan' => $period->month, 'tahun' => $period->year],
                    $item + ['bulan' => $period->month, 'tahun' => $period->year]
                );
            }

            Tagihan::generate($period->month, $period->year, $this->admin);
        }

        Tagihan::where('bulan', now()->month)->where('tahun', now()->year)->orderBy('id')->get()
            ->each(function (Tagihan $tagihan, int $index) {
                $status = match (true) {
                    $index < 22 => 'lunas',
                    $index < 32 => 'pending_transfer',
                    $index < 40 => 'pending_offline',
                    default => 'belum_bayar',
                };

                $tagihan->update([
                    'status' => $status,
                    'payment_method' => match ($status) {
                        'pending_transfer' => 'transfer',
                        'pending_offline', 'lunas' => 'offline',
                        default => 'none',
                    },
                    'verification_status' => match ($status) {
                        'lunas' => 'valid',
                        'pending_transfer', 'pending_offline' => 'menunggu',
                        default => 'belum_dikirim',
                    },
                    'transaction_number' => $status === 'belum_bayar' ? null : Tagihan::nextTransactionNumber(),
                    'note' => $status === 'pending_offline' ? 'Warga akan setor langsung ke bendahara.' : null,
                    'verified_by' => $status === 'lunas' ? $this->bendahara->id : null,
                    'verified_at' => $status === 'lunas' ? now()->subDays(($index % 12) + 1) : null,
                    'paid_at' => $status === 'lunas' ? now()->subDays(($index % 12) + 1) : null,
                ]);

                if ($status === 'lunas') {
                    KasMasuk::updateOrCreate(
                        ['tagihan_id' => $tagihan->id],
                        [
                            'user_id' => $tagihan->user_id,
                            'rt_id' => $tagihan->rt_id,
                            'keterangan' => 'Pembayaran '.$tagihan->display_title.' '.$tagihan->rumah?->kode_rumah,
                            'jumlah' => $tagihan->total,
                            'tanggal' => $tagihan->paid_at,
                        ]
                    );
                }
            });

        $rt01 = $this->rts->firstWhere('name', 'RT 01');
        $iuranKhusus = IuranKhusus::updateOrCreate(
            ['billing_group' => 'insidental_perbaikan_gapura_demo'],
            [
                'rt_id' => $rt01->id,
                'created_by' => $this->bendahara->id,
                'jenis' => 'pembangunan',
                'judul' => 'Iuran Perbaikan Gapura RT 01',
                'keterangan' => 'Demo iuran khusus untuk renovasi gapura lingkungan.',
                'nominal_per_warga' => 25000,
                'tanggal_kejadian' => now()->subDays(8),
                'total_tagihan' => 10,
                'total_terkumpul' => 175000,
            ]
        );

        $this->rumahs->where('rt_id', $rt01->id)->where('status', 'aktif')->take(10)->each(function (Rumah $rumah, int $index) use ($iuranKhusus) {
            $user = $rumah->penanggungJawab;
            if (! $user) {
                return;
            }

            Tagihan::updateOrCreate(
                [
                    'rumah_id' => $rumah->id,
                    'bulan' => now()->month,
                    'tahun' => now()->year,
                    'billing_group' => $iuranKhusus->billing_group,
                ],
                [
                    'user_id' => $user->id,
                    'rt_id' => $rumah->rt_id,
                    'judul' => $iuranKhusus->judul,
                    'total' => $iuranKhusus->nominal_per_warga,
                    'status' => $index < 7 ? 'lunas' : 'belum_bayar',
                    'payment_method' => $index < 7 ? 'offline' : 'none',
                    'verification_status' => $index < 7 ? 'valid' : 'belum_dikirim',
                    'verified_by' => $index < 7 ? $this->bendahara->id : null,
                    'verified_at' => $index < 7 ? now()->subDays(2) : null,
                    'paid_at' => $index < 7 ? now()->subDays(2) : null,
                ]
            );
        });

        foreach ([
            ['Pembelian sapu dan trash bag kerja bakti', 245000, 'RT 01', 10],
            ['Perbaikan lampu jalan Blok B', 375000, 'RT 02', 8],
            ['Konsumsi rapat koordinasi RW', 310000, null, 6],
            ['Servis sound system balai warga', 450000, null, 3],
            ['Beli cat marka jalan', 280000, 'RT 03', 2],
        ] as [$keterangan, $jumlah, $rtName, $days]) {
            KasKeluar::updateOrCreate(
                ['keterangan' => $keterangan],
                [
                    'rt_id' => $rtName ? $this->rts->firstWhere('name', $rtName)?->id : null,
                    'jumlah' => $jumlah,
                    'tanggal' => now()->subDays($days),
                ]
            );
        }
    }

    private function createPengaduanDanSurat(): void
    {
        $pengaduan = [
            ['Lampu jalan depan pos redup', 'Infrastruktur', 'proses'],
            ['Jadwal angkut sampah terlambat', 'Kebersihan', 'pending'],
            ['Parkir tamu menghalangi gang', 'Keamanan', 'selesai'],
            ['Usulan kerja bakti bulanan', 'Sosial', 'selesai'],
            ['Drainase depan blok C mampet', 'Lingkungan', 'pending'],
        ];

        foreach ($pengaduan as $index => [$judul, $kategori, $status]) {
            Pengaduan::updateOrCreate(
                ['judul' => $judul],
                [
                    'user_id' => $this->wargaUsers[$index]->id,
                    'kategori' => $kategori,
                    'deskripsi' => 'Data demo untuk presentasi Smart RW. Pengaduan ini menunjukkan alur warga ke pengurus.',
                    'status' => $status,
                    'tanggapan' => $status === 'pending' ? null : 'Sudah diterima dan ditindaklanjuti pengurus.',
                    'tanggapan_at' => $status === 'pending' ? null : now()->subDays(1),
                ]
            );
        }

        foreach ([
            ['domisili', 'Keperluan pembukaan rekening', 'submitted'],
            ['pengantar_skck', 'Pengantar SKCK untuk melamar kerja', 'verified_rt'],
            ['tidak_mampu', 'Pengajuan bantuan pendidikan', 'approved_rt'],
            ['keterangan_usaha', 'Legalitas UMKM warga', 'done'],
        ] as $index => [$type, $purpose, $status]) {
            $requiresRw = Surat::TYPES[$type]['requires_rw'];

            Surat::updateOrCreate(
                ['verification_code' => 'DEMO-SURAT-'.($index + 1)],
                [
                    'user_id' => $this->wargaUsers[$index + 2]->id,
                    'rt_id' => $this->wargaUsers[$index + 2]->rt_id,
                    'surat_number' => $status === 'done' ? sprintf('470/%03d/RW05/%s', $index + 1, now()->year) : null,
                    'type' => $type,
                    'subject' => Surat::TYPES[$type]['label'],
                    'purpose' => $purpose,
                    'content' => 'Konten surat demo untuk kebutuhan presentasi aplikasi.',
                    'requires_rw' => $requiresRw,
                    'status' => $status,
                    'submitted_at' => now()->subDays(4 - $index),
                    'verified_rt_by' => in_array($status, ['verified_rt', 'approved_rt', 'verified_rw', 'done'], true) ? $this->sekretaris->id : null,
                    'verified_rt_at' => in_array($status, ['verified_rt', 'approved_rt', 'verified_rw', 'done'], true) ? now()->subDays(3) : null,
                    'approved_rt_by' => in_array($status, ['approved_rt', 'verified_rw', 'done'], true) ? $this->ketuaRt->id : null,
                    'approved_rt_at' => in_array($status, ['approved_rt', 'verified_rw', 'done'], true) ? now()->subDays(2) : null,
                    'verified_rw_by' => $status === 'done' && $requiresRw ? $this->ketuaRw->id : null,
                    'verified_rw_at' => $status === 'done' && $requiresRw ? now()->subDay() : null,
                    'approved_rw_by' => $status === 'done' && $requiresRw ? $this->ketuaRw->id : null,
                    'approved_rw_at' => $status === 'done' && $requiresRw ? now()->subDay() : null,
                ]
            );
        }
    }

    private function createKegiatan(): void
    {
        $items = [
            ['Kerja Bakti Bersih Drainase', 'RT 01', now()->addDays(3), now()->addDays(3)->addHours(3), 'Gang Melati RT 01', 'direncanakan', 450000],
            ['Rapat Evaluasi Keamanan RW', null, now()->addDays(6), now()->addDays(6)->addHours(2), 'Balai RW 05', 'direncanakan', 300000],
            ['Senam Pagi Warga', null, now()->subDays(2), now()->subDays(2)->addHours(2), 'Lapangan RW 05', 'selesai', 250000],
            ['Pelatihan Bank Sampah', null, now()->subDays(10), now()->subDays(10)->addHours(2), 'Pos Bank Sampah', 'selesai', 600000],
        ];

        foreach ($items as [$nama, $rtName, $mulai, $selesai, $lokasi, $status, $biaya]) {
            $rt = $rtName ? $this->rts->firstWhere('name', $rtName) : null;
            $kegiatan = Kegiatan::updateOrCreate(
                ['nama' => $nama],
                [
                    'rw_id' => $this->rw->id,
                    'rt_id' => $rt?->id,
                    'created_by' => $this->sekretaris->id,
                    'deskripsi' => 'Agenda demo untuk memperlihatkan fitur kegiatan dan kehadiran warga.',
                    'tanggal_mulai' => $mulai,
                    'tanggal_selesai' => $selesai,
                    'lokasi' => $lokasi,
                    'estimasi_biaya' => $biaya,
                    'realisasi_biaya' => $status === 'selesai' ? $biaya - 50000 : 0,
                    'status' => $status,
                ]
            );

            $this->wargaUsers->take(8)->each(function (User $user) use ($kegiatan) {
                KegiatanHadir::updateOrCreate(
                    ['kegiatan_id' => $kegiatan->id, 'user_id' => $user->id],
                    ['konfirmasi_at' => now()->subDays(1), 'catatan' => 'Hadir']
                );
            });
        }
    }

    private function createAsetDanFasilitas(): void
    {
        $rt01 = $this->rts->firstWhere('name', 'RT 01');

        $asetRt = Aset::updateOrCreate(
            ['nama' => 'Tenda Lipat RT 01'],
            [
                'rt_id' => $rt01->id,
                'rw_id' => null,
                'scope' => 'rt',
                'kategori' => 'tenda_dan_terpal',
                'deskripsi' => 'Tenda untuk kegiatan warga.',
                'jumlah_total' => 4,
                'kondisi' => 'baik',
                'nilai_perkiraan' => 3500000,
                'tanggal_pengadaan' => now()->subMonths(8),
                'lokasi_penyimpanan' => 'Gudang RT 01',
                'is_active' => true,
            ]
        );

        $asetRw = Aset::updateOrCreate(
            ['nama' => 'Sound System RW 05'],
            [
                'rt_id' => $rt01->id,
                'rw_id' => $this->rw->id,
                'scope' => 'rw',
                'kategori' => 'elektronik',
                'deskripsi' => 'Sound system untuk rapat dan acara besar.',
                'jumlah_total' => 2,
                'kondisi' => 'baik',
                'nilai_perkiraan' => 5000000,
                'tanggal_pengadaan' => now()->subYear(),
                'lokasi_penyimpanan' => 'Balai RW',
                'is_active' => true,
            ]
        );

        PeminjamanAset::updateOrCreate(
            ['aset_id' => $asetRt->id, 'pemohon_id' => $this->wargaUsers[0]->id],
            [
                'diproses_oleh' => $this->ketuaRt->id,
                'tanggal_mulai' => now()->addDays(4),
                'tanggal_selesai' => now()->addDays(5),
                'keperluan' => 'Acara tasyakuran keluarga',
                'jumlah_dipinjam' => 1,
                'status' => 'disetujui',
                'catatan_pemohon' => 'Dipakai di halaman rumah.',
                'catatan_pengurus' => 'Disetujui, harap dikembalikan bersih.',
                'tanggal_diproses' => now()->subDay(),
            ]
        );

        PeminjamanAset::updateOrCreate(
            ['aset_id' => $asetRw->id, 'pemohon_id' => $this->wargaUsers[1]->id],
            [
                'diproses_oleh' => null,
                'tanggal_mulai' => now()->addDays(7),
                'tanggal_selesai' => now()->addDays(7),
                'keperluan' => 'Pengajian warga',
                'jumlah_dipinjam' => 1,
                'status' => 'diajukan',
                'catatan_pemohon' => 'Butuh microphone dan speaker.',
            ]
        );

        $fasilitas = collect([
            ['Pos Satpam RW 05', 'pos_satpam', null, 'baik'],
            ['Lampu Jalan Blok A', 'penerangan', $rt01->id, 'perlu_perhatian'],
            ['Taman Bermain Anak', 'taman', null, 'baik'],
            ['Drainase Blok C', 'drainase', $this->rts->firstWhere('name', 'RT 03')?->id, 'rusak'],
        ])->map(fn ($item) => Fasilitas::updateOrCreate(
            ['nama' => $item[0]],
            [
                'rw_id' => $this->rw->id,
                'rt_id' => $item[2],
                'kategori' => $item[1],
                'lokasi_blok' => $item[0],
                'lokasi_deskripsi' => 'Lokasi demo fasilitas '.$item[0],
                'kondisi' => $item[3],
                'catatan' => 'Data demo fasilitas.',
                'is_active' => true,
            ]
        ));

        PengaduanFasilitas::updateOrCreate(
            ['fasilitas_id' => $fasilitas[1]->id, 'pelapor_id' => $this->wargaUsers[3]->id],
            [
                'jenis_masalah' => 'mati',
                'deskripsi' => 'Lampu jalan kadang mati saat malam.',
                'status' => 'ditindaklanjuti',
                'ditindaklanjuti_oleh' => $this->ketuaRt->id,
                'catatan_tindak_lanjut' => 'Sudah dijadwalkan penggantian lampu.',
            ]
        );
    }

    private function createBankSampah(): void
    {
        $jenis = collect([
            ['Plastik Botol', 'kg', 2500],
            ['Kardus', 'kg', 1800],
            ['Kertas Campur', 'kg', 1200],
            ['Minyak Jelantah', 'liter', 4500],
        ])->map(fn ($item) => JenisSampah::updateOrCreate(
            ['rw_id' => $this->rw->id, 'nama' => $item[0]],
            ['satuan' => $item[1], 'harga_per_satuan' => $item[2], 'deskripsi' => 'Jenis sampah demo', 'is_active' => true]
        ));

        $this->wargaUsers->take(8)->each(function (User $user, int $index) use ($jenis) {
            $jenisSampah = $jenis[$index % $jenis->count()];
            $berat = 2 + $index;
            $nilai = (int) ($berat * $jenisSampah->harga_per_satuan);
            $saldo = SaldoSampah::getOrCreate($user, $this->rw->id);
            $sebelum = $saldo->saldo;

            $setoran = SetoranSampah::updateOrCreate(
                ['rw_id' => $this->rw->id, 'warga_id' => $user->id, 'jenis_sampah_id' => $jenisSampah->id, 'tanggal_setor' => now()->subDays($index + 1)->toDateString()],
                [
                    'petugas_id' => $index < 6 ? $this->petugasBankSampah->id : null,
                    'estimasi_berat' => $berat,
                    'berat_aktual' => $index < 6 ? $berat : null,
                    'nilai' => $index < 6 ? $nilai : 0,
                    'status' => $index < 6 ? 'diverifikasi' : 'menunggu',
                    'metode_setor' => $index % 2 === 0 ? 'langsung_petugas' : 'setor_mandiri',
                    'catatan_warga' => 'Setoran demo bank sampah.',
                    'catatan_petugas' => $index < 6 ? 'Sudah ditimbang.' : null,
                    'verified_at' => $index < 6 ? now()->subDays($index) : null,
                ]
            );

            if ($index < 6) {
                $saldo->update([
                    'saldo' => $sebelum + $nilai,
                    'total_setor' => $saldo->total_setor + $nilai,
                ]);

                TransaksiSampah::updateOrCreate(
                    ['referensi_type' => SetoranSampah::class, 'referensi_id' => $setoran->id],
                    [
                        'warga_id' => $user->id,
                        'rw_id' => $this->rw->id,
                        'tipe' => 'masuk',
                        'kategori' => 'setoran',
                        'jumlah' => $nilai,
                        'saldo_sebelum' => $sebelum,
                        'saldo_sesudah' => $sebelum + $nilai,
                        'keterangan' => 'Saldo dari setoran sampah.',
                    ]
                );
            }
        });

        $hadiah = HadiahSampah::updateOrCreate(
            ['rw_id' => $this->rw->id, 'nama' => 'Voucher Sembako Rp25.000'],
            ['deskripsi' => 'Hadiah demo penukaran poin sampah.', 'nilai_tukar' => 25000, 'stok' => 12, 'is_active' => true]
        );

        PenukaranHadiah::updateOrCreate(
            ['warga_id' => $this->wargaUsers[0]->id, 'hadiah_id' => $hadiah->id],
            ['petugas_id' => $this->petugasBankSampah->id, 'nilai_tukar_saat_itu' => 25000, 'status' => 'menunggu', 'catatan' => 'Ambil saat jadwal bank sampah.']
        );

        PenarikanSampah::updateOrCreate(
            ['warga_id' => $this->wargaUsers[1]->id, 'rw_id' => $this->rw->id],
            ['petugas_id' => $this->petugasBankSampah->id, 'jumlah' => 15000, 'status' => 'dibayar', 'catatan_warga' => 'Tarik saldo demo.', 'catatan_petugas' => 'Dibayar tunai.', 'dibayar_at' => now()->subDay()]
        );

        PenjualanSampah::updateOrCreate(
            ['rw_id' => $this->rw->id, 'jenis_sampah_id' => $jenis[0]->id, 'tanggal_jual' => now()->subDays(3)->toDateString()],
            ['petugas_id' => $this->petugasBankSampah->id, 'berat_total' => 48.5, 'harga_jual' => 3200, 'total' => 155200, 'nama_pengepul' => 'Pengepul Berkah', 'catatan' => 'Penjualan demo ke pengepul.']
        );
    }

    private function createUmkm(): void
    {
        $data = [
            ['Warung Nasi Bu Siti', 'makanan_minuman', 'Nasi uduk, gorengan, dan kopi pagi.', 'Nasi Uduk Komplit', 15000],
            ['Laundry Melati Express', 'jasa', 'Cuci kiloan dan setrika cepat.', 'Cuci Setrika / Kg', 7000],
            ['Kue Kering Dapur Rina', 'makanan_minuman', 'Aneka kue rumahan untuk acara warga.', 'Nastar Toples', 65000],
            ['Bengkel Sepeda Pak Agus', 'jasa', 'Servis sepeda anak dan dewasa.', 'Servis Ringan', 25000],
        ];

        foreach ($data as $index => [$nama, $kategori, $deskripsi, $produk, $harga]) {
            $pemilik = $this->wargaUsers[$index + 5];
            $umkm = Umkm::updateOrCreate(
                ['pemilik_id' => $pemilik->id, 'nama_usaha' => $nama],
                [
                    'rw_id' => $this->rw->id,
                    'rt_id' => $pemilik->rt_id,
                    'kategori' => $kategori,
                    'deskripsi' => $deskripsi,
                    'alamat_lokasi' => $pemilik->rumah?->alamat,
                    'nomor_whatsapp' => $pemilik->phone,
                    'status' => $index === 3 ? 'pending' : 'approved',
                    'catatan_pengurus' => $index === 3 ? null : 'UMKM aktif untuk demo.',
                    'diproses_oleh' => $index === 3 ? null : $this->ketuaRt->id,
                    'diproses_at' => $index === 3 ? null : now()->subDays(5),
                ]
            );

            ProdukUmkm::updateOrCreate(
                ['umkm_id' => $umkm->id, 'nama_produk' => $produk],
                ['deskripsi' => 'Produk demo UMKM warga.', 'harga' => $harga, 'satuan_harga' => $kategori === 'jasa' ? 'layanan' : 'pcs', 'is_available' => true]
            );

            foreach (range(1, 7) as $hari) {
                JamOperasionalUmkm::updateOrCreate(
                    ['umkm_id' => $umkm->id, 'hari' => $hari],
                    ['is_tutup' => $hari === 7, 'jam_buka' => $hari === 7 ? null : '08:00', 'jam_tutup' => $hari === 7 ? null : '20:00']
                );
            }
        }
    }

    private function createKoperasi(): void
    {
        $anggota = $this->wargaUsers->take(12);

        $anggota->each(function (User $user, int $index) {
            KoperasiMember::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'member_number' => sprintf('KOP-RW05-%04d', $index + 1),
                    'joined_at' => now()->subMonths(3)->addDays($index)->toDateString(),
                    'status' => $index === 11 ? 'pending' : 'aktif',
                ]
            );

            if ($index < 11) {
                foreach ([
                    ['pokok', 100000],
                    ['wajib', $index === 0 ? 75000 : 50000],
                    ['sukarela', 25000 + ($index * 5000)],
                ] as [$type, $amount]) {
                    KoperasiSimpanan::updateOrCreate(
                        ['user_id' => $user->id, 'type' => $type, 'transaction_date' => now()->subDays(20 - $index)->toDateString()],
                        ['amount' => $amount, 'status' => 'terverifikasi', 'verified_by' => $this->bendahara->id, 'verified_at' => now()->subDays(10)]
                    );
                }
            }
        });

        KoperasiSimpanan::updateOrCreate(
            ['user_id' => $this->wargaUsers[12]->id, 'type' => 'wajib', 'transaction_date' => now()->toDateString()],
            ['amount' => 50000, 'status' => 'pending']
        );

        $pinjaman = KoperasiPinjam::updateOrCreate(
            ['user_id' => $this->wargaUsers[0]->id, 'amount' => 1000000],
            [
                'tenor_months' => 10,
                'service_fee_percentage' => 2,
                'service_fee_amount' => 200000,
                'remaining_amount' => 960000,
                'status' => 'disetujui',
                'approved_by' => $this->bendahara->id,
                'approved_at' => now()->subDays(12),
            ]
        );

        foreach ([120000, 120000] as $index => $amount) {
            KoperasiAngsuran::updateOrCreate(
                ['koperasi_pinjam_id' => $pinjaman->id, 'paid_at' => now()->subDays(8 - ($index * 4))->toDateString()],
                ['amount' => $amount, 'status' => 'terverifikasi', 'verified_by' => $this->bendahara->id, 'verified_at' => now()->subDays(7 - ($index * 4))]
            );
        }

        KoperasiPinjam::updateOrCreate(
            ['user_id' => $this->wargaUsers[2]->id, 'amount' => 750000],
            ['tenor_months' => 6, 'service_fee_percentage' => 2, 'service_fee_amount' => 90000, 'remaining_amount' => 840000, 'status' => 'menunggu_persetujuan']
        );
    }

    private function createPosyandu(): void
    {
        $this->wargaUsers->take(8)->each(function (User $orangTua, int $index) {
            $tanggalLahir = now()->subMonths(8 + ($index * 5))->subDays($index * 2);

            $balita = Balita::updateOrCreate(
                ['nik' => $this->number16(3273800000000000, $index + 1)],
                [
                    'rw_id' => $this->rw->id,
                    'rt_id' => $orangTua->rt_id,
                    'rumah_id' => $orangTua->rumah_id,
                    'orang_tua_id' => $orangTua->id,
                    'no_kk' => $orangTua->warga?->kartuKeluarga?->no_kk,
                    'nama' => ['Alya', 'Rafa', 'Kenzie', 'Naura', 'Daffa', 'Zahra', 'Arkan', 'Mika'][$index],
                    'jenis_kelamin' => $index % 2 === 0 ? 'perempuan' : 'laki_laki',
                    'tanggal_lahir' => $tanggalLahir,
                    'berat_lahir_kg' => 2.8 + ($index / 10),
                    'panjang_lahir_cm' => 48 + $index,
                    'nama_ibu' => $orangTua->name,
                    'nama_ayah' => 'Ayah '.$orangTua->name,
                    'catatan' => 'Data demo posyandu.',
                    'is_active' => true,
                ]
            );

            foreach ([2, 1, 0] as $offset) {
                $tanggal = now()->subMonths($offset);
                $usiaBulan = max(0, (int) floor($tanggalLahir->diffInMonths($tanggal)));

                PemeriksaanPosyandu::updateOrCreate(
                    ['balita_id' => $balita->id, 'tanggal_pemeriksaan' => $tanggal->toDateString()],
                    [
                        'petugas_id' => $this->petugasPosyandu->id,
                        'usia_hari' => (int) floor($tanggalLahir->diffInDays($tanggal)),
                        'usia_bulan' => $usiaBulan,
                        'berat_kg' => 7 + ($usiaBulan * 0.18) + ($index / 10),
                        'panjang_tinggi_cm' => 65 + ($usiaBulan * 0.7),
                        'metode_ukur_tinggi' => $usiaBulan < 24 ? 'telentang' : 'berdiri',
                        'lingkar_kepala_cm' => 42 + ($index / 10),
                        'lingkar_lengan_cm' => 13 + ($index / 10),
                        'z_score_bb_u' => -0.2 + ($index / 20),
                        'status_bb_u' => $index === 7 ? 'risiko_berat_badan_lebih' : 'normal',
                        'versi_standar' => 'WHO 2006',
                        'vitamin_a' => $offset === 0,
                        'catatan' => 'Pemeriksaan demo rutin.',
                    ]
                );
            }
        });
    }

    private function phoneFor(string $prefix, int $index): string
    {
        return $prefix.str_pad((string) $index, 8, '0', STR_PAD_LEFT);
    }

    private function number16(int $base, int $index): string
    {
        return (string) ($base + $index);
    }
}
