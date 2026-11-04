<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoperasiPinjam extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'tenor_months',
        'service_fee_percentage',
        'service_fee_amount',
        'remaining_amount',
        'proof_path',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'service_fee_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function angsurans()
    {
        return $this->hasMany(KoperasiAngsuran::class);
    }
}
