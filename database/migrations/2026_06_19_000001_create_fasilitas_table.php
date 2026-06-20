<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rts')->nullOnDelete();
            $table->string('nama');
            $table->string('kategori');
            $table->string('lokasi_blok')->nullable();
            $table->text('lokasi_deskripsi')->nullable();
            $table->string('kondisi')->default('baik');
            $table->string('foto')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rw_id', 'kategori']);
            $table->index(['rw_id', 'kondisi']);
            $table->index(['rt_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas');
    }
};
