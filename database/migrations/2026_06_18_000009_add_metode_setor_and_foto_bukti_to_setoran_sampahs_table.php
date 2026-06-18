<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setoran_sampahs', function (Blueprint $table) {
            $table->string('metode_setor')->default('langsung_petugas')->after('status');
            $table->string('foto_bukti')->nullable()->after('catatan_warga');
        });
    }

    public function down(): void
    {
        Schema::table('setoran_sampahs', function (Blueprint $table) {
            $table->dropColumn(['metode_setor', 'foto_bukti']);
        });
    }
};
