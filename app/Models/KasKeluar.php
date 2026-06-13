<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasKeluar extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'keterangan',
        'jumlah',
        'tanggal',
        'bukti'
    ];
}
