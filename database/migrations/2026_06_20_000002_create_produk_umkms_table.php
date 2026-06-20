<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_umkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga')->nullable();
            $table->string('satuan_harga')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['umkm_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_umkms');
    }
};
