<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPatroli extends Model
{
    protected $table = 'log_patrolis';

    protected $fillable = [
        'jadwal_shift_id',
        'waktu_patroli',
        'catatan',
        'ada_kejadian',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'waktu_patroli' => 'datetime',
            'ada_kejadian' => 'boolean',
        ];
    }

    public function jadwalShift(): BelongsTo
    {
        return $this->belongsTo(JadwalShiftSatpam::class, 'jadwal_shift_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
