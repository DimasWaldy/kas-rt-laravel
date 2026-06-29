<?php

use App\Models\Balita;
use App\Models\PemeriksaanPosyandu;
use App\Models\User;
use App\Models\WhoGrowthStandard;
use Database\Seeders\PosyanduDemoSeeder;

test('seeder demo posyandu aman dijalankan ulang dan menghasilkan alur demo lengkap', function () {
    $this->seed(PosyanduDemoSeeder::class);
    $this->seed(PosyanduDemoSeeder::class);

    expect(WhoGrowthStandard::count())->toBe(122)
        ->and(User::where('email', 'petugas.posyandu@smart-rw.test')->count())->toBe(1)
        ->and(User::where('email', 'warga.posyandu.rt01@smart-rw.test')->count())->toBe(1)
        ->and(User::where('email', 'warga.posyandu.rt02@smart-rw.test')->count())->toBe(1)
        ->and(Balita::whereIn('nama', ['Aisyah Putri', 'Bagas Pratama'])->count())->toBe(2)
        ->and(PemeriksaanPosyandu::count())->toBe(19);

    expect(PemeriksaanPosyandu::whereNull('z_score_bb_u')->exists())->toBeFalse()
        ->and(PemeriksaanPosyandu::whereNull('status_bb_u')->exists())->toBeFalse();
});
