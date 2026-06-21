<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class JamOperasionalUmkm extends Model
{
    public const HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    protected $fillable = [
        'umkm_id',
        'hari',
        'is_tutup',
        'jam_buka',
        'jam_tutup',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'is_tutup' => 'boolean',
            'jam_buka' => 'datetime:H:i',
            'jam_tutup' => 'datetime:H:i',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function getHariLabelAttribute(): string
    {
        return self::HARI[$this->hari] ?? '-';
    }

    public function getJamTeksAttribute(): string
    {
        if ($this->is_tutup) {
            return 'Tutup';
        }

        if (! $this->jam_buka || ! $this->jam_tutup) {
            return '-';
        }

        return $this->jam_buka->format('H:i').' - '.$this->jam_tutup->format('H:i');
    }

    public static function getHariIni(): int
    {
        return Carbon::now()->dayOfWeekIso;
    }
}
