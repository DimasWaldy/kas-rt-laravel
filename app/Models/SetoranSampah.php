<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranSampah extends Model
{
    protected $fillable = [
        'rw_id',
        'warga_id',
        'petugas_id',
        'jenis_sampah_id',
        'estimasi_berat',
        'berat_aktual',
        'nilai',
        'status',
        'metode_setor',
        'catatan_warga',
        'foto_bukti',
        'catatan_petugas',
        'tanggal_setor',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'estimasi_berat' => 'float',
            'berat_aktual' => 'float',
            'nilai' => 'integer',
            'tanggal_setor' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warga_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function jenisSampah(): BelongsTo
    {
        return $this->belongsTo(JenisSampah::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu Verifikasi',
            'diverifikasi' => 'Sudah Diverifikasi',
            'ditolak' => 'Ditolak',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'bg-amber-50 text-amber-700 border-amber-200',
            'diverifikasi' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'ditolak' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    public function getMetodeSetorLabelAttribute(): string
    {
        return match ($this->metode_setor) {
            'langsung_petugas' => 'Langsung ke Petugas',
            'setor_mandiri' => 'Setor Mandiri',
            default => str($this->metode_setor)->headline()->toString(),
        };
    }
}
