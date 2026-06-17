<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HadiahSampah extends Model
{
    protected $fillable = [
        'rw_id',
        'nama',
        'deskripsi',
        'foto',
        'nilai_tukar',
        'stok',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nilai_tukar' => 'integer',
            'stok' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function penukarans(): HasMany
    {
        return $this->hasMany(PenukaranHadiah::class, 'hadiah_id');
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->stok > 0;
    }
}
