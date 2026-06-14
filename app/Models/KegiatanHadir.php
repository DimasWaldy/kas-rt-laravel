<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanHadir extends Model
{
    use HasFactory;

    protected $fillable = [
        'kegiatan_id',
        'user_id',
        'konfirmasi_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'konfirmasi_at' => 'datetime',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
