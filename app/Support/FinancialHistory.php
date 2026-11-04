<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class FinancialHistory
{
    public const DELETION_MESSAGE = 'Akun yang memiliki riwayat keuangan tidak dapat dihapus. Hubungi pengurus untuk penanganan akun tanpa menghilangkan catatan transaksi.';

    public static function existsForUser(int $userId): bool
    {
        // Query the tables directly so soft-deleted financial records are retained too.
        foreach ([
            'kas' => 'user_id',
            'kas_masuks' => 'user_id',
            'tagihans' => 'user_id',
            'koperasi_simpanans' => 'user_id',
            'koperasi_pinjams' => 'user_id',
            'saldo_sampahs' => 'warga_id',
            'transaksi_sampahs' => 'warga_id',
            'setoran_sampahs' => 'warga_id',
            'penarikan_sampahs' => 'warga_id',
            'penukaran_hadiahs' => 'warga_id',
            'penjualan_sampahs' => 'petugas_id',
        ] as $table => $column) {
            if (DB::table($table)->where($column, $userId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
