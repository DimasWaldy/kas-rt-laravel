<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Aset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rt_id',
        'nama',
        'kategori',
        'deskripsi',
        'jumlah_total',
        'kondisi',
        'nilai_perkiraan',
        'tanggal_pengadaan',
        'lokasi_penyimpanan',
        'foto',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nilai_perkiraan' => 'integer',
            'tanggal_pengadaan' => 'date',
            'is_active' => 'boolean',
            'jumlah_total' => 'integer',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function peminjamanAset(): HasMany
    {
        return $this->hasMany(PeminjamanAset::class);
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'furniture' => 'Furniture',
            'elektronik' => 'Elektronik',
            'tenda_dan_terpal' => 'Tenda & Terpal',
            'kebersihan' => 'Kebersihan',
            'olahraga' => 'Olahraga',
            'lainnya' => 'Lainnya',
            default => str($this->kategori)->headline()->toString(),
        };
    }

    public function getKondisiLabelAttribute(): string
    {
        return match ($this->kondisi) {
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
            default => str($this->kondisi)->headline()->toString(),
        };
    }

    public function getKondisiColorAttribute(): string
    {
        return match ($this->kondisi) {
            'baik' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rusak_ringan' => 'bg-amber-50 text-amber-700 border-amber-200',
            'rusak_berat' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    public function getJumlahTersediaAttribute(): int
    {
        $sedangDipinjam = $this->peminjamanAset()
            ->whereIn('status', ['disetujui', 'dipinjam'])
            ->count();

        return max(0, $this->jumlah_total - $sedangDipinjam);
    }

    public function isAvailableOn(Carbon $tanggalMulai, Carbon $tanggalSelesai, ?int $excludeId = null): bool
    {
        $query = $this->peminjamanAset()
            ->whereIn('status', ['disetujui', 'dipinjam'])
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $tanggalMulai);

        if ($excludeId) {
            $query->whereKeyNot($excludeId);
        }

        return ! $query->exists();
    }
}
