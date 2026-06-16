<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts');
            $table->string('nama');
            $table->string('kategori');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('jumlah_total')->default(1);
            $table->string('kondisi')->default('baik');
            $table->unsignedBigInteger('nilai_perkiraan')->nullable();
            $table->date('tanggal_pengadaan')->nullable();
            $table->string('lokasi_penyimpanan')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rt_id', 'kategori']);
            $table->index(['rt_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};
