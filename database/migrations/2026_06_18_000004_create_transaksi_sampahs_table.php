<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->string('tipe');
            $table->string('kategori');
            $table->unsignedBigInteger('jumlah');
            $table->unsignedBigInteger('saldo_sebelum');
            $table->unsignedBigInteger('saldo_sesudah');
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('referensi_type')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['warga_id', 'created_at']);
            $table->index(['rw_id', 'kategori']);
            $table->index(['referensi_type', 'referensi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_sampahs');
    }
};
