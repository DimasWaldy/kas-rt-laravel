<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('warga_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('jenis_sampah_id')->constrained('jenis_sampahs')->cascadeOnDelete();
            $table->decimal('estimasi_berat', 8, 2)->nullable();
            $table->decimal('berat_aktual', 8, 2)->nullable();
            $table->unsignedBigInteger('nilai')->default(0);
            $table->string('status')->default('menunggu');
            $table->string('catatan_warga')->nullable();
            $table->string('catatan_petugas')->nullable();
            $table->date('tanggal_setor');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['rw_id', 'status']);
            $table->index(['warga_id', 'status']);
            $table->index('tanggal_setor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_sampahs');
    }
};
