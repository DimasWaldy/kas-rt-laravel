<?php

namespace Database\Seeders;

use App\Services\Posyandu\WhoWfaXlsxImporter;
use Illuminate\Database\Seeder;

class WhoGrowthStandardSeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(WhoWfaXlsxImporter::class);
        $basePath = database_path('data/who');

        $girls = $importer->import(
            $basePath.'/wfa_girls_0-to-5-years_zscores.xlsx',
            'perempuan'
        );
        $boys = $importer->import(
            $basePath.'/wfa_boys_0-to-5-years_zscores.xlsx',
            'laki_laki'
        );

        $this->command?->info(
            "Standar WHO WFA berhasil diimpor: {$girls} perempuan, {$boys} laki-laki."
        );
    }
}
