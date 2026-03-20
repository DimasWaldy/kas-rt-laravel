<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasMasuk extends Model
{
    protected $fillable = [
        'user_id',
        'keterangan',
        'jumlah',
        'tanggal'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
