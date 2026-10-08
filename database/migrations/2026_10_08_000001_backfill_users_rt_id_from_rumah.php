<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('rt_id')
            ->whereNotNull('rumah_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rumahs')
                    ->whereColumn('rumahs.id', 'users.rumah_id')
                    ->whereNotNull('rumahs.rt_id');
            })
            ->update([
                'rt_id' => DB::raw('(select rumahs.rt_id from rumahs where rumahs.id = users.rumah_id and rumahs.rt_id is not null limit 1)'),
            ]);

        DB::table('users')
            ->whereNull('rt_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('wargas')
                    ->join('rumahs', 'rumahs.id', '=', 'wargas.rumah_diajukan_id')
                    ->whereColumn('wargas.user_id', 'users.id')
                    ->whereNotNull('rumahs.rt_id');
            })
            ->update([
                'rt_id' => DB::raw('(select rumahs.rt_id from wargas join rumahs on rumahs.id = wargas.rumah_diajukan_id where wargas.user_id = users.id and rumahs.rt_id is not null limit 1)'),
            ]);
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversed.
    }
};
