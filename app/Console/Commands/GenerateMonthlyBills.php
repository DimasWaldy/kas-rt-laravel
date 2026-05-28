<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tagihan;
use App\Models\IuranBulanan;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'bills:generate';
    protected $description = 'Otomatis menyalin iuran dari bulan sebelumnya dan membangkitkan tagihan warga';

    public function handle()
    {
        $now = now();
        $bulan = $now->month;
        $tahun = $now->year;

        $this->info("Mengecek iuran untuk periode {$bulan}/{$tahun}...");

        // 1. Cek iuran bulan ini
        $hasIuran = IuranBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

        if (!$hasIuran) {
            $lastMonth = $now->copy()->subMonth();
            $prevItems = IuranBulanan::where('bulan', $lastMonth->month)->where('tahun', $lastMonth->year)->get();

            if ($prevItems->isNotEmpty()) {
                $this->info("Menyalin data dari bulan lalu...");
                foreach ($prevItems as $item) {
                    IuranBulanan::create([
                        'nama' => $item->nama,
                        'keterangan' => $item->keterangan,
                        'jumlah' => $item->jumlah,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'is_wajib' => $item->is_wajib,
                    ]);
                }
            } else {
                $this->info("Data bulan lalu kosong. Menggunakan setelan default RT...");
                $defaults = [
                    ['nama' => 'Iuran Kebersihan', 'jumlah' => 20000, 'ket' => 'Iuran rutin kebersihan lingkungan', 'wajib' => true],
                    ['nama' => 'Iuran Keamanan', 'jumlah' => 15000, 'ket' => 'Iuran rutin keamanan/satpam', 'wajib' => true],
                    ['nama' => 'Dana Sosial', 'jumlah' => 5000, 'ket' => 'Dana sumbangan sukarela', 'wajib' => false],
                ];

                foreach ($defaults as $d) {
                    IuranBulanan::create([
                        'nama' => $d['nama'],
                        'keterangan' => $d['ket'],
                        'jumlah' => $d['jumlah'],
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'is_wajib' => $d['wajib'],
                    ]);
                }
            }
        }

        // 2. Bangkitkan tagihan KK untuk kepala keluarga
        Tagihan::generateForMonth($bulan, $tahun);
        $this->info("Selesai! Tagihan keluarga telah berhasil diproses untuk kepala keluarga.");
    }
}
