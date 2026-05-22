<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class KasKeluar extends Model
{
    use Auditable;
    protected $fillable = [
        'keterangan',
        'jumlah',
        'tanggal',
        'bukti'
    ];
}
