<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan_posyandus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balita_id')->constrained('balitas')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_pemeriksaan');
            $table->unsignedInteger('usia_hari');
            $table->unsignedTinyInteger('usia_bulan');
            $table->decimal('berat_kg', 5, 2);
            $table->decimal('panjang_tinggi_cm', 5, 2)->nullable();
            $table->string('metode_ukur_tinggi')->nullable();
            $table->decimal('lingkar_kepala_cm', 5, 2)->nullable();
            $table->decimal('lingkar_lengan_cm', 5, 2)->nullable();
            $table->decimal('z_score_bb_u', 6, 3)->nullable();
            $table->string('status_bb_u')->nullable();
            $table->string('versi_standar')->nullable();
            $table->boolean('vitamin_a')->default(false);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['balita_id', 'tanggal_pemeriksaan']);
            $table->index(['tanggal_pemeriksaan', 'status_bb_u']);
            $table->index('petugas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_posyandus');
    }
};
