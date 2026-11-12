<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->timestamp('koperasi_verified_at')->nullable()->after('uid_hash');
        });

        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_id']);
            $table->dropUnique(['kegiatan_id']);
        });

        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->nullable()->change();
            $table->index('kegiatan_id');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->cascadeOnDelete();
            $table->string('scope_type', 20)->default('kegiatan')->after('id');
            $table->string('scope_key', 64)->nullable()->unique()->after('scope_type');
            $table->foreignId('rw_id')->nullable()->after('kegiatan_id')->constrained('rws')->cascadeOnDelete();
            $table->foreignId('rt_id')->nullable()->after('rw_id')->constrained('rts')->cascadeOnDelete();
        });

        DB::table('rfid_sessions')
            ->whereNull('scope_key')
            ->whereNotNull('kegiatan_id')
            ->get(['id', 'kegiatan_id'])
            ->each(fn ($row) => DB::table('rfid_sessions')
                ->where('id', $row->id)
                ->update([
                    'scope_type' => 'kegiatan',
                    'scope_key' => 'kegiatan:'.$row->kegiatan_id,
                ]));
    }

    public function down(): void
    {
        DB::table('rfid_sessions')->whereNull('kegiatan_id')->delete();

        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->dropForeign(['rw_id']);
            $table->dropForeign(['rt_id']);
            $table->dropUnique(['scope_key']);
            $table->dropForeign(['kegiatan_id']);
            $table->dropIndex(['kegiatan_id']);
            $table->dropColumn(['scope_type', 'scope_key', 'rw_id', 'rt_id']);
        });

        Schema::table('rfid_sessions', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->nullable(false)->change();
            $table->unique('kegiatan_id');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->cascadeOnDelete();
        });

        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->dropColumn('koperasi_verified_at');
        });
    }
};
