<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasMasuk extends Model
{
    use Auditable, HasRtScope, SoftDeletes;
    protected $fillable = [
        'user_id',
        'rt_id',
        'keterangan',
        'jumlah',
        'tanggal',
        'tagihan_id',
        'bukti',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
