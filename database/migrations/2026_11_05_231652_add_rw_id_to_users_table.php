<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $column = $table->foreignId('rw_id')->nullable();
            // SQLite cannot reliably roll back this FK when legacy users columns are rebuilt in tests.
            if (DB::getDriverName() !== 'sqlite') {
                $column->constrained('rws')->nullOnDelete();
            }
        });

        // Backfill only RW-scoped accounts with an actual RT assignment; never guess missing placement.
        DB::table('users')->whereNull('rw_id')->whereNotNull('rt_id')
            ->whereIn('role_id', DB::table('roles')->whereIn('name', [
                'ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu',
            ])->select('id'))
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('rts')
                    ->whereColumn('rts.id', 'users.rt_id');
            })
            ->update(['rw_id' => DB::raw('(select rts.rw_id from rts where rts.id = users.rt_id)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropColumn('rw_id');
            } else {
                $table->dropConstrainedForeignId('rw_id');
            }
        });
    }
};
