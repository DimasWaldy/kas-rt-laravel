<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiSampah extends Model
{
    protected $fillable = [
        'warga_id',
        'rw_id',
        'tipe',
        'kategori',
        'jumlah',
        'saldo_sebelum',
        'saldo_sesudah',
        'referensi_id',
        'referensi_type',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'saldo_sebelum' => 'integer',
            'saldo_sesudah' => 'integer',
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
}
