<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhoGrowthStandard extends Model
{
    public const INDICATOR_WEIGHT_FOR_AGE = 'wfa';

    public const VERSION = 'WHO Child Growth Standards';

    protected $fillable = [
        'indicator',
        'jenis_kelamin',
        'usia_bulan',
        'l',
        'm',
        's',
        'sd3neg',
        'sd2neg',
        'sd1neg',
        'sd0',
        'sd1',
        'sd2',
        'sd3',
        'versi_standar',
        'source_file',
        'source_checksum',
    ];

    protected function casts(): array
    {
        return [
            'usia_bulan' => 'integer',
            'l' => 'float',
            'm' => 'float',
            's' => 'float',
            'sd3neg' => 'float',
            'sd2neg' => 'float',
            'sd1neg' => 'float',
            'sd0' => 'float',
            'sd1' => 'float',
            'sd2' => 'float',
            'sd3' => 'float',
        ];
    }

    public function scopeWeightForAge(Builder $query): Builder
    {
        return $query->where('indicator', self::INDICATOR_WEIGHT_FOR_AGE);
    }

    public function scopeForGender(Builder $query, string $jenisKelamin): Builder
    {
        return $query->where('jenis_kelamin', $jenisKelamin);
    }
}
