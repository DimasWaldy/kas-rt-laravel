<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GerbangLog extends Model
{
    protected $fillable = [
        'rw_id',
        'rt_id',
        'user_id',
        'rfid_card_id',
        'uid_masked',
        'arah',
        'status',
        'device_id',
        'keterangan',
        'tapped_at',
    ];

    protected function casts(): array
    {
        return [
            'tapped_at' => 'datetime',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rfidCard(): BelongsTo
    {
        return $this->belongsTo(RfidCard::class);
    }
}
