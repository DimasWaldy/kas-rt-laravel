<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenukaranHadiah extends Model
{
    protected $fillable = [
        'warga_id',
        'hadiah_id',
        'petugas_id',
        'nilai_tukar_saat_itu',
        'status',
        'catatan',
        'diberikan_at',
    ];

    protected function casts(): array
    {
        return [
            'nilai_tukar_saat_itu' => 'integer',
            'diberikan_at' => 'datetime',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warga_id');
    }

    public function hadiah(): BelongsTo
    {
        return $this->belongsTo(HadiahSampah::class, 'hadiah_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu Pengambilan',
            'diberikan' => 'Sudah Diberikan',
            'dibatalkan' => 'Dibatalkan',
            default => str($this->status)->headline()->toString(),
        };
    }
}
