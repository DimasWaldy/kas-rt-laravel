<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IuranBulanan extends Model
{
    protected $fillable = [
        'nama',
        'keterangan',
        'jumlah',
        'bulan',
        'tahun',
        'is_wajib',
    ];

    protected $casts = [
        'is_wajib' => 'boolean',
    ];

    public static function totalForMonth(int $bulan, int $tahun): int
    {
        return (int) self::where('bulan', $bulan)->where('tahun', $tahun)->sum('jumlah');
    }

    public function scopeForMonth($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }
}
