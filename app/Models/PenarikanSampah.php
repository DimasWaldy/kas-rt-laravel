<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenarikanSampah extends Model
{
    protected $fillable = [
        'warga_id',
        'rw_id',
        'petugas_id',
        'jumlah',
        'status',
        'catatan_warga',
        'catatan_petugas',
        'dibayar_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'dibayar_at' => 'datetime',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warga_id');
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu Pembayaran',
            'dibayar' => 'Sudah Dibayar',
            'dibatalkan' => 'Dibatalkan',
            default => str($this->status)->headline()->toString(),
        };
    }
}
