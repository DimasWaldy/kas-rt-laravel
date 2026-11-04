<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemeriksaanPosyandu extends Model
{
    protected $fillable = [
        'balita_id',
        'petugas_id',
        'tanggal_pemeriksaan',
        'usia_hari',
        'usia_bulan',
        'berat_kg',
        'panjang_tinggi_cm',
        'metode_ukur_tinggi',
        'lingkar_kepala_cm',
        'lingkar_lengan_cm',
        'z_score_bb_u',
        'status_bb_u',
        'versi_standar',
        'vitamin_a',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pemeriksaan' => 'date',
            'usia_hari' => 'integer',
            'usia_bulan' => 'integer',
            'berat_kg' => 'float',
            'panjang_tinggi_cm' => 'float',
            'lingkar_kepala_cm' => 'float',
            'lingkar_lengan_cm' => 'float',
            'z_score_bb_u' => 'float',
            'vitamin_a' => 'boolean',
        ];
    }

    public function balita(): BelongsTo
    {
        return $this->belongsTo(Balita::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
