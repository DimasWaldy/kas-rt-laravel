<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoperasiAngsuran extends Model
{
    protected $fillable = [
        'koperasi_pinjam_id',
        'amount',
        'paid_at',
        'proof_path',
        'status',
        'verified_by',
        'verified_at',
        'rejected_reason',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'verified_at' => 'datetime',
    ];

    public function pinjaman()
    {
        return $this->belongsTo(KoperasiPinjam::class, 'koperasi_pinjam_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
