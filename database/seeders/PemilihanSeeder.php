<?php

namespace Database\Seeders;

use App\Models\Pemilihan;
use App\Models\PemilihanKandidat;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Database\Seeder;

class PemilihanSeeder extends Seeder
{
    public function run(): void
    {
        $rw = Rw::first();
        $rt = Rt::first();

        if (! $rt) {
            return;
        }

        $pemilihan = Pemilihan::updateOrCreate(
            ['slug' => 'pemilihan-ketua-rt-01-2026-2031'],
            [
                'rt_id' => $rt->id,
                'rw_id' => $rw?->id,
                'title' => 'Pemilihan Ketua RT 01 Periode 2026-2031',
                'scope_type' => 'rt',
                'description' => 'Pesta demokrasi pemilihan Ketua RT 01 masa bakti 2026-2031 berbasis e-KTP fisik. Berikan hak suara Anda secara bijak dan rahasia demi kemajuan lingkungan rukun tetangga.',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth()->addDays(7),
                'status' => 'aktif',
            ]
        );

        PemilihanKandidat::updateOrCreate(
            [
                'pemilihan_id' => $pemilihan->id,
                'nomor_urut' => 1,
            ],
            [
                'nama_calon' => 'Bambang Sudarsono, S.T.',
                'nama_wakil' => 'Ahmad Fauzi',
                'slogan' => 'Lingkungan Asri, Aman, dan Berbasis Digital',
                'visi' => 'Menjadikan RT 01 sebagai lingkungan percontohan yang modern, aman, transparan dalam pengelolaan kas, dan guyub rukun antarwarga.',
                'misi' => "1. Pelayanan administrasi surat dan pengaduan serba cepat via aplikasi Smart RW.\n2. Mengoptimalkan jadwal siskamling ronda malam warga dan penerangan jalan.\n3. Laporan keuangan kas RT terbuka dan dapat diakses setiap saat oleh warga.",
            ]
        );

        PemilihanKandidat::updateOrCreate(
            [
                'pemilihan_id' => $pemilihan->id,
                'nomor_urut' => 2,
            ],
            [
                'nama_calon' => 'Hj. Ratna Sari Dewi',
                'nama_wakil' => 'Dr. Hendra Gunawan',
                'slogan' => 'Harmoni Warga, Gotong Royong, dan Pemberdayaan Keluarga',
                'visi' => 'Mewujudkan RT 01 yang religius, peduli kesehatan balita dan lansia, serta memajukan ekonomi warga melalui koperasi lingkungan.',
                'misi' => "1. Meningkatkan fasilitas Posyandu balita dan senam lansia berkala.\n2. Mendukung program bank sampah dan permodalan UMKM warga.\n3. Mengaktifkan kegiatan silaturahmi keluarga dan kerja bakti rutin tiap bulan.",
            ]
        );
    }
}
