<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisSampah extends Model
{
    protected $fillable = [
        'rw_id',
        'nama',
        'satuan',
        'harga_per_satuan',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga_per_satuan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function getSatuanLabelAttribute(): string
    {
        return match ($this->satuan) {
            'kg' => 'Kilogram',
            'pcs' => 'Pcs',
            'liter' => 'Liter',
            default => ucfirst((string) $this->satuan),
        };
    }
}
