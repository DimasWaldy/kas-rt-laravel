<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws');
            $table->foreignId('created_by')->constrained('users');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('estimasi_biaya')->default(0);
            $table->unsignedBigInteger('realisasi_biaya')->default(0);
            $table->string('status')->default('akan_datang');
            $table->text('catatan_pembatalan')->nullable();
            $table->timestamps();

            $table->index(['rw_id', 'status']);
            $table->index('tanggal_mulai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatans');
    }
};
