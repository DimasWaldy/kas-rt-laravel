<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class KasMasuk extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id',
        'keterangan',
        'jumlah',
        'tanggal',
        'tagihan_id',
        'bukti',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
