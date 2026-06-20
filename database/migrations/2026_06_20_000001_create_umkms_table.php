<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->foreignId('pemilik_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_usaha');
            $table->string('kategori');
            $table->text('deskripsi');
            $table->string('alamat_lokasi')->nullable();
            $table->string('nomor_whatsapp');
            $table->string('jam_operasional')->nullable();
            $table->string('foto_usaha')->nullable();
            $table->string('status')->default('pending');
            $table->string('catatan_pengurus')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();

            $table->index(['rw_id', 'status']);
            $table->index(['rw_id', 'kategori', 'status']);
            $table->index(['pemilik_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkms');
    }
};
