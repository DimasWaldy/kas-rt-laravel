<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoperasiSimpanan extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'transaction_date',
        'proof_path',
        'status',
        'verified_by',
        'verified_at',
        'rejected_reason',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
