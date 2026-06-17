<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->string('nama');
            $table->string('satuan')->default('kg');
            $table->unsignedBigInteger('harga_per_satuan');
            $table->string('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['rw_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_sampahs');
    }
};
