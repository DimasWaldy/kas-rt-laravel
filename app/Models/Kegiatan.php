<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'rw_id',
        'created_by',
        'nama',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'foto',
        'estimasi_biaya',
        'realisasi_biaya',
        'status',
        'catatan_pembatalan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'estimasi_biaya' => 'integer',
            'realisasi_biaya' => 'integer',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hadirs(): HasMany
    {
        return $this->hasMany(KegiatanHadir::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereDate('tanggal_mulai', '>=', today())
            ->orderBy('tanggal_mulai');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query) {
                $query->whereDate('tanggal_selesai', '<', today())
                    ->orWhere('status', 'selesai');
            })
            ->orderByDesc('tanggal_mulai');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'akan_datang' => 'Akan Datang',
            'berlangsung' => 'Berlangsung',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'akan_datang' => 'bg-blue-50 text-blue-700 border-blue-200',
            'berlangsung' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'selesai' => 'bg-slate-50 text-slate-600 border-slate-200',
            'dibatalkan' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }
}
