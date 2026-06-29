<?php

namespace App\Services\Posyandu;

use App\Models\Balita;
use App\Models\WhoGrowthStandard;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use RuntimeException;

class WeightForAgeCalculator
{
    public const STATUS_LABELS = [
        'berat_sangat_kurang' => 'Berat Badan Sangat Kurang',
        'berat_kurang' => 'Berat Badan Kurang',
        'normal' => 'Berat Badan Normal',
        'di_atas_rentang_normal' => 'Di Atas Rentang Normal BB/U',
    ];

    public function calculate(
        string $jenisKelamin,
        CarbonInterface $tanggalLahir,
        CarbonInterface $tanggalPemeriksaan,
        float $beratKg,
    ): WeightForAgeResult {
        if (! array_key_exists($jenisKelamin, Balita::JENIS_KELAMIN)) {
            throw new DomainException('Jenis kelamin balita tidak valid.');
        }

        if (! is_finite($beratKg) || $beratKg <= 0) {
            throw new DomainException('Berat badan harus lebih dari 0 kg.');
        }

        $lahir = CarbonImmutable::instance($tanggalLahir)->startOfDay();
        $pemeriksaan = CarbonImmutable::instance($tanggalPemeriksaan)->startOfDay();

        if ($pemeriksaan->lessThan($lahir)) {
            throw new DomainException('Tanggal pemeriksaan tidak boleh sebelum tanggal lahir.');
        }

        if ($pemeriksaan->greaterThan($lahir->addYears(5))) {
            throw new DomainException('Standar WHO WFA ini hanya berlaku sampai usia 5 tahun.');
        }

        $usiaHari = (int) floor($lahir->diffInDays($pemeriksaan));
        $calendarDifference = $lahir->diff($pemeriksaan);
        $usiaBulan = ($calendarDifference->y * 12) + $calendarDifference->m;

        $reference = WhoGrowthStandard::query()
            ->weightForAge()
            ->forGender($jenisKelamin)
            ->where('usia_bulan', $usiaBulan)
            ->first();

        if (! $reference) {
            throw new RuntimeException(
                "Referensi WHO WFA {$jenisKelamin} usia {$usiaBulan} bulan tidak ditemukan."
            );
        }

        $zScore = $this->calculateZScore($reference, $beratKg);
        $status = $this->classify($zScore);

        return new WeightForAgeResult(
            usiaHari: $usiaHari,
            usiaBulan: $usiaBulan,
            zScore: round($zScore, 3),
            status: $status,
            statusLabel: self::STATUS_LABELS[$status],
            versiStandar: $reference->versi_standar,
            referenceId: $reference->id,
        );
    }

    private function calculateZScore(WhoGrowthStandard $reference, float $beratKg): float
    {
        if ($beratKg <= $reference->sd3neg) {
            $distance = $reference->sd2neg - $reference->sd3neg;
            if ($distance <= 0) {
                throw new RuntimeException('Rentang SD negatif pada referensi WHO tidak valid.');
            }

            return -3 + (($beratKg - $reference->sd3neg) / $distance);
        }

        if ($beratKg >= $reference->sd3) {
            $distance = $reference->sd3 - $reference->sd2;
            if ($distance <= 0) {
                throw new RuntimeException('Rentang SD positif pada referensi WHO tidak valid.');
            }

            return 3 + (($beratKg - $reference->sd3) / $distance);
        }

        if (abs($reference->l) < 0.0000001) {
            return log($beratKg / $reference->m) / $reference->s;
        }

        return ((($beratKg / $reference->m) ** $reference->l) - 1)
            / ($reference->l * $reference->s);
    }

    private function classify(float $zScore): string
    {
        if ($zScore < -3) {
            return 'berat_sangat_kurang';
        }

        if ($zScore < -2) {
            return 'berat_kurang';
        }

        if ($zScore <= 1) {
            return 'normal';
        }

        return 'di_atas_rentang_normal';
    }
}
