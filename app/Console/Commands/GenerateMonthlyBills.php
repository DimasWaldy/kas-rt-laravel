<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tagihan;
use App\Models\IuranBulanan;
use Illuminate\Support\Facades\Validator;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'bills:generate {--bulan= : Bulan tagihan (1-12)} {--tahun= : Tahun tagihan}';
    protected $description = 'Otomatis menyalin iuran dari bulan sebelumnya dan membangkitkan tagihan warga';

    public function handle(): int
    {
        $period = Validator::make([
            'bulan' => $this->option('bulan') ?? now()->month,
            'tahun' => $this->option('tahun') ?? now()->year,
        ], ['bulan' => ['required', 'integer', 'between:1,12'], 'tahun' => ['required', 'integer', 'min:2024', 'max:' . (now()->year + 5)]]);
        if ($period->fails()) {
            foreach ($period->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }
        $bulan = (int) $period->validated()['bulan'];
        $tahun = (int) $period->validated()['tahun'];
        $now = \Carbon\Carbon::create($tahun, $bulan, 1);

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

        // 2. Bangkitkan tagihan KK untuk kepala keluarga.
        // Tagihan::generate() idempotent: kombinasi rumah + periode + billing_group
        // yang sudah ada tidak akan di-insert ulang.
        $result = Tagihan::generate($bulan, $tahun);

        $this->info('Selesai! Tagihan keluarga telah diproses.');
        $this->line('Tagihan baru dibuat: ' . $result['created']);
        $this->line('Tagihan dilewati karena sudah ada: ' . $result['skipped']);
        $this->line('Tagihan yang diperbarui nominal/detailnya: ' . $result['updated']);
        $this->line('Rumah aktif diperiksa: ' . $result['active_houses']);
        $this->line('Rumah belum dapat ditagih: ' . count($result['unbilled_houses']));
        if ($result['unbilled_houses']) {
            $this->warn('Penetapan PJ iuran diperlukan. Buka Data Rumah > Perlu PJ Iuran, tetapkan PJ, lalu jalankan generate ulang untuk periode yang sama.');
            $this->table(['Kode rumah', 'Alasan belum dapat ditagih'], array_map(
                fn (array $house) => [$house['kode_rumah'], $house['reason']], $result['unbilled_houses']
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
