<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasKeluar extends Model
{
    use Auditable, HasRtScope, SoftDeletes;

    protected $fillable = [
        'keterangan',
        'rt_id',
        'jumlah',
        'tanggal',
        'bukti'
    ];
}
