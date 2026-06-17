<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penukaran_hadiahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hadiah_id')->constrained('hadiah_sampahs')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('nilai_tukar_saat_itu');
            $table->string('status')->default('menunggu');
            $table->string('catatan')->nullable();
            $table->timestamp('diberikan_at')->nullable();
            $table->timestamps();

            $table->index(['warga_id', 'status']);
            $table->index(['hadiah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penukaran_hadiahs');
    }
};
