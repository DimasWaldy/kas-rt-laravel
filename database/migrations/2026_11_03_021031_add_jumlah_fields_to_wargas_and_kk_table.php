<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            $table->unsignedTinyInteger('jumlah_penghuni_rumah')->nullable()->after('ajukan_pj_iuran');
        });

        Schema::table('kartu_keluargas', function (Blueprint $table) {
            $table->unsignedTinyInteger('jumlah_anggota')->nullable()->after('nama_kepala_keluarga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            $table->dropColumn('jumlah_penghuni_rumah');
        });

        Schema::table('kartu_keluargas', function (Blueprint $table) {
            $table->dropColumn('jumlah_anggota');
        });
    }
};
