<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanAset extends Model
{
    protected $fillable = [
        'aset_id',
        'pemohon_id',
        'diproses_oleh',
        'tanggal_mulai',
        'tanggal_selesai',
        'keperluan',
        'jumlah_dipinjam',
        'status',
        'catatan_pemohon',
        'catatan_pengurus',
        'tanggal_diproses',
        'tanggal_dipinjam',
        'tanggal_dikembalikan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_diproses' => 'datetime',
            'tanggal_dipinjam' => 'datetime',
            'tanggal_dikembalikan' => 'datetime',
            'jumlah_dipinjam' => 'integer',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diajukan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'dipinjam' => 'Sedang Dipinjam',
            'dikembalikan' => 'Dikembalikan',
            'ditolak' => 'Ditolak',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'diajukan' => 'bg-blue-50 text-blue-700 border-blue-200',
            'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dipinjam' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dikembalikan' => 'bg-slate-50 text-slate-600 border-slate-200',
            'ditolak' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    public function getDurasiHariAttribute(): int
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }
}
