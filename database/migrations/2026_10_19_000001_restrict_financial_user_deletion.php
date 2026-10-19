<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $references = [
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
    ];

    public function up(): void
    {
        foreach ($this->references as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
                $blueprint->foreign($column)->references('id')->on('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->references as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                $blueprint->dropForeign([$column]);
                $foreign = $blueprint->foreign($column)->references('id')->on('users');
                $table === 'kas_masuks' ? $foreign->nullOnDelete() : $foreign->cascadeOnDelete();
            });
        }
    }
};
