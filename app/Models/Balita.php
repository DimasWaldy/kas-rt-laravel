<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Balita extends Model
{
    public const JENIS_KELAMIN = [
        'laki_laki' => 'Laki-laki',
        'perempuan' => 'Perempuan',
    ];

    protected $fillable = [
        'rw_id',
        'rt_id',
        'rumah_id',
        'orang_tua_id',
        'nik',
        'no_kk',
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'berat_lahir_kg',
        'panjang_lahir_cm',
        'nama_ibu',
        'nama_ayah',
        'catatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'berat_lahir_kg' => 'float',
            'panjang_lahir_cm' => 'float',
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

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
    }

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orang_tua_id');
    }

    public function pemeriksaans(): HasMany
    {
        return $this->hasMany(PemeriksaanPosyandu::class)->orderBy('tanggal_pemeriksaan');
    }

    public function pemeriksaanTerakhir(): HasOne
    {
        return $this->hasOne(PemeriksaanPosyandu::class)->latestOfMany('tanggal_pemeriksaan');
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return self::JENIS_KELAMIN[$this->jenis_kelamin] ?? '-';
    }

    public function getUsiaSekarangHariAttribute(): int
    {
        if ($this->tanggal_lahir->isAfter(today())) {
            return 0;
        }

        return (int) floor($this->tanggal_lahir->diffInDays(today()));
    }

    public function getUsiaSekarangBulanAttribute(): int
    {
        if ($this->tanggal_lahir->isAfter(today())) {
            return 0;
        }

        return (int) floor($this->tanggal_lahir->diffInMonths(today()));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
