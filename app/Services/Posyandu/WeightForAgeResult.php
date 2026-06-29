<?php

namespace App\Services\Posyandu;

final readonly class WeightForAgeResult
{
    public function __construct(
        public int $usiaHari,
        public int $usiaBulan,
        public float $zScore,
        public string $status,
        public string $statusLabel,
        public string $versiStandar,
        public int $referenceId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'usia_hari' => $this->usiaHari,
            'usia_bulan' => $this->usiaBulan,
            'z_score_bb_u' => $this->zScore,
            'status_bb_u' => $this->status,
            'versi_standar' => $this->versiStandar,
        ];
    }
}
