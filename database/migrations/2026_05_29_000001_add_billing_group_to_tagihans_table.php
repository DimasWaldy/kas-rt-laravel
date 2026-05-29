<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihans', 'billing_group')) {
            Schema::table('tagihans', function (Blueprint $table) {
                $table->string('billing_group')->default('iuran_rutin')->after('tahun');
            });
        }

        if (! Schema::hasColumn('tagihans', 'judul')) {
            Schema::table('tagihans', function (Blueprint $table) {
                $table->string('judul')->nullable()->after('billing_group');
            });
        }

        Schema::table('tagihans', function (Blueprint $table) {
            $table->index('user_id', 'tagihans_user_id_fk_index');
            $table->index('rumah_id', 'tagihans_rumah_id_fk_index');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            try {
                $table->dropUnique(['user_id', 'bulan', 'tahun']);
            } catch (\Throwable) {
                //
            }

            try {
                $table->dropUnique(['rumah_id', 'bulan', 'tahun']);
            } catch (\Throwable) {
                //
            }

            $table->unique(['user_id', 'bulan', 'tahun', 'billing_group'], 'tagihans_user_period_group_unique');
            $table->unique(['rumah_id', 'bulan', 'tahun', 'billing_group'], 'tagihans_rumah_period_group_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropUnique('tagihans_user_period_group_unique');
            $table->dropUnique('tagihans_rumah_period_group_unique');

            $table->unique(['user_id', 'bulan', 'tahun']);
            $table->unique(['rumah_id', 'bulan', 'tahun']);
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropColumn(['billing_group', 'judul']);
        });
    }
};
