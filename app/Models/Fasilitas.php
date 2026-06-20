<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    protected $fillable = [
        'rw_id',
        'rt_id',
        'nama',
        'kategori',
        'lokasi_blok',
        'lokasi_deskripsi',
        'kondisi',
        'foto',
        'catatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function pengaduanFasilitas(): HasMany
    {
        return $this->hasMany(PengaduanFasilitas::class);
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'cctv' => 'CCTV',
            'pos_satpam' => 'Pos Satpam',
            'lapangan' => 'Lapangan',
            'taman' => 'Taman',
            'jalan' => 'Jalan',
            'drainase' => 'Drainase/Got',
            'penerangan' => 'Penerangan Jalan',
            'lainnya' => 'Lainnya',
            default => str($this->kategori)->headline()->toString(),
        };
    }

    public function getKondisiLabelAttribute(): string
    {
        return match ($this->kondisi) {
            'baik' => 'Baik',
            'perlu_perhatian' => 'Perlu Perhatian',
            'rusak' => 'Rusak',
            default => str($this->kondisi)->headline()->toString(),
        };
    }

    public function getKondisiColorAttribute(): string
    {
        return match ($this->kondisi) {
            'baik' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'perlu_perhatian' => 'bg-amber-50 text-amber-700 border-amber-200',
            'rusak' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    public function getLokasiLengkapAttribute(): string
    {
        return collect([
            $this->lokasi_blok,
            $this->rt?->name,
            $this->rw?->name,
        ])
            ->filter(fn($value) => filled($value))
            ->implode(', ');
    }
}
