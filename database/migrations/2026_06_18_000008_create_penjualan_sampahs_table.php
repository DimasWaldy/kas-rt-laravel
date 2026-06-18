<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jenis_sampah_id')->constrained('jenis_sampahs')->cascadeOnDelete();
            $table->date('tanggal_jual');
            $table->decimal('berat_total', 10, 2);
            $table->unsignedBigInteger('harga_jual');
            $table->unsignedBigInteger('total');
            $table->string('nama_pengepul')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index(['rw_id', 'tanggal_jual']);
            $table->index(['jenis_sampah_id', 'tanggal_jual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_sampahs');
    }
};
