<?php

namespace App\Services;

use App\Models\PenarikanSampah;
use App\Models\PenukaranHadiah;
use App\Models\SaldoSampah;
use App\Models\SetoranSampah;
use App\Models\TransaksiSampah;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class BankSampahService
{
    public function kreditSaldo(
        User $warga,
        int $rwId,
        int $jumlah,
        string $kategori,
        int $referensiId,
        string $referensiType,
        string $keterangan = ''
    ): SaldoSampah {
        return DB::transaction(function () use ($warga, $rwId, $jumlah, $kategori, $referensiId, $referensiType, $keterangan) {
            $saldo = SaldoSampah::getOrCreate($warga, $rwId);
            $saldo = SaldoSampah::whereKey($saldo->id)->lockForUpdate()->firstOrFail();

            $saldoSebelum = $saldo->saldo;
            $saldoSesudah = $saldoSebelum + $jumlah;

            $updates = [
                'saldo' => $saldoSesudah,
            ];

            if ($kategori === 'setoran') {
                $updates['total_setor'] = $saldo->total_setor + $jumlah;
            }

            $saldo->update($updates);

            TransaksiSampah::create([
                'warga_id' => $warga->id,
                'rw_id' => $rwId,
                'tipe' => 'kredit',
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'referensi_id' => $referensiId,
                'referensi_type' => $referensiType,
                'keterangan' => $keterangan,
            ]);

            return $saldo->fresh();
        });
    }

    public function debitSaldo(
        User $warga,
        int $rwId,
        int $jumlah,
        string $kategori,
        int $referensiId,
        string $referensiType,
        string $keterangan = ''
    ): SaldoSampah {
        return DB::transaction(function () use ($warga, $rwId, $jumlah, $kategori, $referensiId, $referensiType, $keterangan) {
            $saldo = SaldoSampah::getOrCreate($warga, $rwId);
            $saldo = SaldoSampah::whereKey($saldo->id)->lockForUpdate()->firstOrFail();

            if ($saldo->saldo < $jumlah) {
                throw new Exception('Saldo bank sampah tidak mencukupi.');
            }

            $saldoSebelum = $saldo->saldo;
            $saldoSesudah = $saldoSebelum - $jumlah;

            $updates = [
                'saldo' => $saldoSesudah,
            ];

            if ($kategori === 'penarikan') {
                $updates['total_tarik'] = $saldo->total_tarik + $jumlah;
            }

            if ($kategori === 'tukar_hadiah') {
                $updates['total_tukar'] = $saldo->total_tukar + $jumlah;
            }

            $saldo->update($updates);

            TransaksiSampah::create([
                'warga_id' => $warga->id,
                'rw_id' => $rwId,
                'tipe' => 'debit',
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'referensi_id' => $referensiId,
                'referensi_type' => $referensiType,
                'keterangan' => $keterangan,
            ]);

            return $saldo->fresh();
        });
    }

    public function verifikasiSetoran(
        SetoranSampah $setoran,
        float $beratAktual,
        User $petugas,
        string $catatan = ''
    ): SetoranSampah {
        return DB::transaction(function () use ($setoran, $beratAktual, $petugas, $catatan) {
            $setoran = SetoranSampah::with(['jenisSampah', 'warga'])
                ->whereKey($setoran->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($setoran->status !== 'menunggu') {
                throw new Exception('Setoran ini sudah diproses.');
            }

            $nilai = (int) round($beratAktual * $setoran->jenisSampah->harga_per_satuan);

            $setoran->update([
                'berat_aktual' => $beratAktual,
                'nilai' => $nilai,
                'status' => 'diverifikasi',
                'petugas_id' => $petugas->id,
                'catatan_petugas' => $catatan,
                'verified_at' => now(),
            ]);

            $this->kreditSaldo(
                $setoran->warga,
                $setoran->rw_id,
                $nilai,
                'setoran',
                $setoran->id,
                SetoranSampah::class,
                'Setoran sampah diverifikasi'
            );

            return $setoran->fresh(['jenisSampah', 'warga', 'petugas']);
        });
    }

    public function prosesPenarikan(
        PenarikanSampah $penarikan,
        User $petugas,
        string $catatan = ''
    ): PenarikanSampah {
        return DB::transaction(function () use ($penarikan, $petugas, $catatan) {
            $penarikan = PenarikanSampah::with('warga')
                ->whereKey($penarikan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($penarikan->status !== 'menunggu') {
                throw new Exception('Penarikan ini sudah diproses.');
            }

            $this->debitSaldo(
                $penarikan->warga,
                $penarikan->rw_id,
                $penarikan->jumlah,
                'penarikan',
                $penarikan->id,
                PenarikanSampah::class,
                'Penarikan saldo bank sampah'
            );

            $penarikan->update([
                'status' => 'dibayar',
                'petugas_id' => $petugas->id,
                'catatan_petugas' => $catatan,
                'dibayar_at' => now(),
            ]);

            return $penarikan->fresh(['warga', 'petugas']);
        });
    }

    public function prosesPenukaran(PenukaranHadiah $penukaran, User $petugas): PenukaranHadiah
    {
        return DB::transaction(function () use ($penukaran, $petugas) {
            $penukaran = PenukaranHadiah::with(['hadiah', 'warga'])
                ->whereKey($penukaran->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($penukaran->status !== 'menunggu') {
                throw new Exception('Penukaran hadiah ini sudah diproses.');
            }

            $hadiah = $penukaran->hadiah()
                ->lockForUpdate()
                ->firstOrFail();

            if ($hadiah->stok <= 0) {
                throw new Exception('Stok hadiah sudah habis.');
            }

            $this->debitSaldo(
                $penukaran->warga,
                $hadiah->rw_id,
                $penukaran->nilai_tukar_saat_itu,
                'tukar_hadiah',
                $penukaran->id,
                PenukaranHadiah::class,
                'Penukaran hadiah bank sampah'
            );

            $hadiah->decrement('stok');

            $penukaran->update([
                'status' => 'diberikan',
                'petugas_id' => $petugas->id,
                'diberikan_at' => now(),
            ]);

            return $penukaran->fresh(['hadiah', 'warga', 'petugas']);
        });
    }
}
