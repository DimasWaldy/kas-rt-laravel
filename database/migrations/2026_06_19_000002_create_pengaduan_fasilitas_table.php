<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan_fasilitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->constrained('fasilitas')->cascadeOnDelete();
            $table->foreignId('pelapor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ditindaklanjuti_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis_masalah');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->string('status')->default('dilaporkan');
            $table->text('catatan_tindak_lanjut')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamps();

            $table->index(['fasilitas_id', 'status']);
            $table->index(['pelapor_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan_fasilitas');
    }
};
