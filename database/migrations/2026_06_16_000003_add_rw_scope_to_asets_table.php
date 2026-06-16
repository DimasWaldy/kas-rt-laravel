<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            $table->foreignId('rw_id')
                ->nullable()
                ->after('rt_id')
                ->constrained('rws')
                ->nullOnDelete();
            $table->string('scope')->default('rt')->after('rw_id');
        });

        DB::table('asets')
            ->orderBy('id')
            ->eachById(function ($aset) {
                if (! $aset->rt_id) {
                    return;
                }

                $rwId = DB::table('rts')
                    ->where('id', $aset->rt_id)
                    ->value('rw_id');

                DB::table('asets')
                    ->where('id', $aset->id)
                    ->update([
                        'rw_id' => $rwId,
                        'scope' => 'rt',
                    ]);
            });

        Schema::table('asets', function (Blueprint $table) {
            $table->foreignId('rt_id')->nullable()->change();
            $table->index(['scope', 'rw_id']);
            $table->index(['scope', 'rt_id']);
        });
    }

    public function down(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            $table->dropIndex(['scope', 'rw_id']);
            $table->dropIndex(['scope', 'rt_id']);
            $table->dropForeign(['rw_id']);
            $table->dropColumn(['rw_id', 'scope']);
            $table->foreignId('rt_id')->nullable(false)->change();
        });
    }
};
