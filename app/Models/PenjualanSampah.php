<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanSampah extends Model
{
    protected $fillable = [
        'rw_id',
        'petugas_id',
        'jenis_sampah_id',
        'tanggal_jual',
        'berat_total',
        'harga_jual',
        'total',
        'nama_pengepul',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_jual' => 'date',
            'berat_total' => 'float',
            'harga_jual' => 'integer',
            'total' => 'integer',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function jenisSampah(): BelongsTo
    {
        return $this->belongsTo(JenisSampah::class);
    }
}
