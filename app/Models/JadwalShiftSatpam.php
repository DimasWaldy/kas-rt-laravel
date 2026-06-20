<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalShiftSatpam extends Model
{
    protected $fillable = [
        'rw_id',
        'nama_satpam',
        'kontak_satpam',
        'shift',
        'jam_mulai',
        'jam_selesai',
        'tanggal',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
            'tanggal' => 'date',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function logPatrolis(): HasMany
    {
        return $this->hasMany(LogPatroli::class, 'jadwal_shift_id');
    }

    public function getShiftLabelAttribute(): string
    {
        return match ($this->shift) {
            'pagi' => 'Shift Pagi (06:00-14:00)',
            'siang' => 'Shift Siang (14:00-22:00)',
            'malam' => 'Shift Malam (22:00-06:00)',
            default => str($this->shift)->headline()->toString(),
        };
    }
}
