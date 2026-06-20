<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaduanFasilitas extends Model
{
    protected $table = 'pengaduan_fasilitas';

    protected $fillable = [
        'fasilitas_id',
        'pelapor_id',
        'ditindaklanjuti_oleh',
        'jenis_masalah',
        'deskripsi',
        'foto',
        'status',
        'catatan_tindak_lanjut',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(Fasilitas::class);
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }

    public function tindakLanjutOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditindaklanjuti_oleh');
    }

    public function getJenisMasalahLabelAttribute(): string
    {
        return match ($this->jenis_masalah) {
            'rusak' => 'Rusak',
            'mati' => 'Mati/Tidak Berfungsi',
            'kotor' => 'Kotor',
            'hilang' => 'Hilang',
            'lainnya' => 'Lainnya',
            default => str($this->jenis_masalah)->headline()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'dilaporkan' => 'Dilaporkan',
            'ditindaklanjuti' => 'Sedang Ditindaklanjuti',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'dilaporkan' => 'bg-amber-50 text-amber-700 border-amber-200',
            'ditindaklanjuti' => 'bg-blue-50 text-blue-700 border-blue-200',
            'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'ditolak' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }
}
