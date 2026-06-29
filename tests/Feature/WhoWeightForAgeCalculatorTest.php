<?php

use App\Models\WhoGrowthStandard;
use App\Services\Posyandu\WeightForAgeCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\WhoGrowthStandardSeeder;

beforeEach(function () {
    $this->seed(WhoGrowthStandardSeeder::class);
    $this->calculator = app(WeightForAgeCalculator::class);
});

test('median who menghasilkan z score nol', function () {
    $reference = WhoGrowthStandard::query()
        ->weightForAge()
        ->forGender('perempuan')
        ->where('usia_bulan', 0)
        ->firstOrFail();

    $result = $this->calculator->calculate(
        'perempuan',
        CarbonImmutable::parse('2026-01-15'),
        CarbonImmutable::parse('2026-01-15'),
        $reference->m,
    );

    expect($result->usiaHari)->toBe(0)
        ->and($result->usiaBulan)->toBe(0)
        ->and($result->zScore)->toBe(0.0)
        ->and($result->status)->toBe('normal');
});

test('calculator menggunakan completed calendar month untuk memilih referensi', function () {
    $reference = WhoGrowthStandard::query()
        ->weightForAge()
        ->forGender('laki_laki')
        ->where('usia_bulan', 1)
        ->firstOrFail();

    $result = $this->calculator->calculate(
        'laki_laki',
        CarbonImmutable::parse('2026-01-15'),
        CarbonImmutable::parse('2026-02-20'),
        $reference->m,
    );

    expect($result->usiaHari)->toBe(36)
        ->and($result->usiaBulan)->toBe(1)
        ->and($result->zScore)->toBe(0.0);
});

test('calculator mengklasifikasikan status bb u berdasarkan z score', function () {
    $reference = WhoGrowthStandard::query()
        ->weightForAge()
        ->forGender('laki_laki')
        ->where('usia_bulan', 12)
        ->firstOrFail();

    $weightForZ = function (float $z) use ($reference): float {
        if (abs($reference->l) < 0.0000001) {
            return $reference->m * exp($reference->s * $z);
        }

        return $reference->m * ((1 + ($reference->l * $reference->s * $z)) ** (1 / $reference->l));
    };

    $calculate = fn (float $weight) => $this->calculator->calculate(
        'laki_laki',
        CarbonImmutable::parse('2025-01-01'),
        CarbonImmutable::parse('2026-01-01'),
        $weight,
    );

    expect($calculate($weightForZ(-3.2))->status)->toBe('berat_sangat_kurang')
        ->and($calculate($weightForZ(-2.5))->status)->toBe('berat_kurang')
        ->and($calculate($weightForZ(0))->status)->toBe('normal')
        ->and($calculate($weightForZ(1.5))->status)->toBe('di_atas_rentang_normal');
});

test('nilai ekstrem memakai extrapolasi linear di luar tiga sd', function () {
    $reference = WhoGrowthStandard::query()
        ->weightForAge()
        ->forGender('perempuan')
        ->where('usia_bulan', 24)
        ->firstOrFail();

    $berat = $reference->sd3neg - ($reference->sd2neg - $reference->sd3neg);
    $result = $this->calculator->calculate(
        'perempuan',
        CarbonImmutable::parse('2024-01-01'),
        CarbonImmutable::parse('2026-01-01'),
        $berat,
    );

    expect($result->zScore)->toBe(-4.0)
        ->and($result->status)->toBe('berat_sangat_kurang');
});

test('calculator menolak tanggal dan usia di luar cakupan standar', function () {
    expect(fn () => $this->calculator->calculate(
        'laki_laki',
        CarbonImmutable::parse('2026-01-02'),
        CarbonImmutable::parse('2026-01-01'),
        3.2,
    ))->toThrow(DomainException::class);

    expect(fn () => $this->calculator->calculate(
        'laki_laki',
        CarbonImmutable::parse('2020-01-01'),
        CarbonImmutable::parse('2026-01-02'),
        20,
    ))->toThrow(DomainException::class);
});
