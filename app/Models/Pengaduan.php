<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengaduan extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'judul',
        'kategori',
        'deskripsi',
        'foto',
        'status',
        'tanggapan',
        'tanggapan_oleh',
        'tanggapan_at',
    ];

    protected $casts = [
        'tanggapan_at' => 'datetime',
    ];

    /**
     * Relasi ke User pembuat pengaduan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User pengurus/admin yang menanggapi pengaduan.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tanggapan_oleh');
    }
}
