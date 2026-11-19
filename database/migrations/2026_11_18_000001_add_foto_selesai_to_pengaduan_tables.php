<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengaduan_fasilitas') && ! Schema::hasColumn('pengaduan_fasilitas', 'foto_selesai')) {
            Schema::table('pengaduan_fasilitas', function (Blueprint $table) {
                $table->string('foto_selesai')->nullable()->after('foto');
            });
        }

        if (Schema::hasTable('pengaduans') && ! Schema::hasColumn('pengaduans', 'foto_selesai')) {
            Schema::table('pengaduans', function (Blueprint $table) {
                $table->string('foto_selesai')->nullable()->after('foto');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaduan_fasilitas') && Schema::hasColumn('pengaduan_fasilitas', 'foto_selesai')) {
            Schema::table('pengaduan_fasilitas', function (Blueprint $table) {
                $table->dropColumn('foto_selesai');
            });
        }

        if (Schema::hasTable('pengaduans') && Schema::hasColumn('pengaduans', 'foto_selesai')) {
            Schema::table('pengaduans', function (Blueprint $table) {
                $table->dropColumn('foto_selesai');
            });
        }
    }
};
