<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('rw_id')->constrained('rws')->cascadeOnDelete();
            $table->unsignedBigInteger('saldo')->default(0);
            $table->unsignedBigInteger('total_setor')->default(0);
            $table->unsignedBigInteger('total_tarik')->default(0);
            $table->unsignedBigInteger('total_tukar')->default(0);
            $table->timestamps();

            $table->index('rw_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_sampahs');
    }
};
