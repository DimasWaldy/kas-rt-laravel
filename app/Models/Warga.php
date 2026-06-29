<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warga extends Model
{
    protected $fillable = [
        'user_id',
        'kartu_keluarga_id',
        'nik',
        'nama_lengkap',
        'status_dalam_kk',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_verifikasi',
        'metode_verifikasi',
        'dokumen_kk',
        'dokumen_ktp',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'catatan_verifikasi',
        'rumah_diajukan',
        'rumah_diajukan_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class);
    }

    public function rumahDiajukan(): BelongsTo
    {
        return $this->belongsTo(Rumah::class, 'rumah_diajukan_id');
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function getStatusVerifikasiLabelAttribute(): string
    {
        return match ($this->status_verifikasi) {
            'pending' => 'Menunggu Verifikasi RT',
            'terverifikasi' => 'Terverifikasi',
            'ditolak' => 'Ditolak',
            default => $this->status_verifikasi,
        };
    }

    public function getRumahAktualAttribute(): ?Rumah
    {
        if ($this->kartu_keluarga_id) {
            return $this->kartuKeluarga?->rumah;
        }

        return $this->rumahDiajukan;
    }

    public function isTerverifikasi(): bool
    {
        return $this->status_verifikasi === 'terverifikasi';
    }
}
