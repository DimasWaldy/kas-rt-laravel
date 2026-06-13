<?php

namespace Database\Seeders;

use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $rw = Rw::updateOrCreate(
            ['name' => 'RW 05'],
            [
                'description' => null,
                'address' => null,
                'kelurahan' => 'Sukamaju',
                'kecamatan' => 'Cibeunying',
                'kota' => 'Bandung',
                'is_active' => true,
            ]
        );

        foreach (range(1, 5) as $number) {
            Rt::updateOrCreate(
                [
                    'rw_id' => $rw->id,
                    'name' => sprintf('RT %02d', $number),
                ],
                [
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
