<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkms', function (Blueprint $table) {
            $table->dropColumn('jam_operasional');
        });

        Schema::create('jam_operasional_umkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari');
            $table->boolean('is_tutup')->default(false);
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->timestamps();

            $table->unique(['umkm_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_operasional_umkms');

        Schema::table('umkms', function (Blueprint $table) {
            $table->string('jam_operasional')->nullable();
        });
    }
};
