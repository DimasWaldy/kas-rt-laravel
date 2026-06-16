<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_asets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('asets')->cascadeOnDelete();
            $table->foreignId('pemohon_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('keperluan');
            $table->unsignedInteger('jumlah_dipinjam')->default(1);
            $table->string('status')->default('diajukan');
            $table->text('catatan_pemohon')->nullable();
            $table->text('catatan_pengurus')->nullable();
            $table->timestamp('tanggal_diproses')->nullable();
            $table->timestamp('tanggal_dipinjam')->nullable();
            $table->timestamp('tanggal_dikembalikan')->nullable();
            $table->timestamps();

            $table->index(['aset_id', 'status']);
            $table->index(['pemohon_id', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_asets');
    }
};
