<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoSampah extends Model
{
    protected $fillable = [
        'warga_id',
        'rw_id',
        'saldo',
        'total_setor',
        'total_tarik',
        'total_tukar',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'integer',
            'total_setor' => 'integer',
            'total_tarik' => 'integer',
            'total_tukar' => 'integer',
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

    public static function getOrCreate(User $warga, int $rwId): self
    {
        return self::firstOrCreate(
            ['warga_id' => $warga->id],
            [
                'rw_id' => $rwId,
                'saldo' => 0,
                'total_setor' => 0,
                'total_tarik' => 0,
                'total_tukar' => 0,
            ]
        );
    }
}
