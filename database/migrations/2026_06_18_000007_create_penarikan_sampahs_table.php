<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('jumlah');
            $table->string('status')->default('menunggu');
            $table->string('catatan_warga')->nullable();
            $table->string('catatan_petugas')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->timestamps();

            $table->index(['warga_id', 'status']);
            $table->index(['rw_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penarikan_sampahs');
    }
};
