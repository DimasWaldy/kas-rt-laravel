<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'status_domisili',
        'mode_domisili',
        'alamat_domisili',
        'catatan_domisili',
        'domisili_diajukan_at',
        'domisili_diproses_oleh',
        'domisili_diproses_at',
        'ajukan_pj_iuran',
        'jumlah_penghuni_rumah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'diverifikasi_at' => 'datetime',
            'domisili_diajukan_at' => 'datetime',
            'domisili_diproses_at' => 'datetime',
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
        if ($this->status_domisili && $this->status_domisili !== 'disetujui') {
            return null;
        }

        if ($this->kartu_keluarga_id) {
            return $this->kartuKeluarga?->rumah;
        }

        return $this->rumahDiajukan;
    }

    public function isTerverifikasi(): bool
    {
        return $this->status_verifikasi === 'terverifikasi';
    }

    public function availableVerificationDocuments(): array
    {
        return array_filter([
            'kk' => $this->dokumen_kk,
            'ktp' => $this->dokumen_ktp,
        ], fn ($path) => filled($path) && Storage::disk('local')->exists($path));
    }

    public function scopeMenungguDomisili(Builder $query): Builder
    {
        return $query->where('status_domisili', 'pending')
            ->where('status_verifikasi', 'terverifikasi')
            ->whereNull('kartu_keluarga_id')
            ->whereHas('user', fn (Builder $user) => $user
                ->where('onboarding_domisili', true)->where('status_akun', 'aktif')
                ->whereNotNull('rt_id')->whereNull('rumah_id')
                ->whereHas('role', fn (Builder $role) => $role->where('name', 'warga')));
    }
}
