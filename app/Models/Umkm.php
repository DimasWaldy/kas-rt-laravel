<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Umkm extends Model
{
    protected $fillable = [
        'rw_id',
        'rt_id',
        'pemilik_id',
        'nama_usaha',
        'kategori',
        'deskripsi',
        'alamat_lokasi',
        'nomor_whatsapp',
        'foto_usaha',
        'status',
        'catatan_pengurus',
        'diproses_oleh',
        'diproses_at',
    ];

    protected function casts(): array
    {
        return [
            'diproses_at' => 'datetime',
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

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function produkUmkms(): HasMany
    {
        return $this->hasMany(ProdukUmkm::class);
    }

    public function jamOperasional(): HasMany
    {
        return $this->hasMany(JamOperasionalUmkm::class)->orderBy('hari');
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'makanan_minuman' => 'Makanan & Minuman',
            'jasa' => 'Jasa',
            'kerajinan' => 'Kerajinan',
            'sembako' => 'Sembako',
            'fashion' => 'Fashion',
            'pertanian' => 'Pertanian',
            'lainnya' => 'Lainnya',
            default => str($this->kategori)->headline()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Aktif',
            'rejected' => 'Ditolak',
            'nonaktif' => 'Nonaktif',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rejected' => 'bg-red-50 text-red-700 border-red-200',
            'nonaktif' => 'bg-slate-50 text-slate-600 border-slate-200',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    public function getWhatsappUrlAttribute(): string
    {
        $nomorBersih = preg_replace('/\D+/', '', $this->nomor_whatsapp) ?? '';

        if (str_starts_with($nomorBersih, '0')) {
            $nomorBersih = '62'.substr($nomorBersih, 1);
        }

        return 'https://wa.me/'.$nomorBersih;
    }

    public function isBukaSekarang(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $jadwalHariIni = $this->jamOperasional
            ->firstWhere('hari', JamOperasionalUmkm::getHariIni());

        if (! $jadwalHariIni
            || $jadwalHariIni->is_tutup
            || ! $jadwalHariIni->jam_buka
            || ! $jadwalHariIni->jam_tutup) {
            return false;
        }

        $sekarang = now()->format('H:i:s');
        $jamBuka = $jadwalHariIni->jam_buka->format('H:i:s');
        $jamTutup = $jadwalHariIni->jam_tutup->format('H:i:s');

        return $sekarang >= $jamBuka && $sekarang < $jamTutup;
    }

    public function getJamOperasionalRingkasAttribute(): string
    {
        $jadwal = $this->jamOperasional;

        if ($jadwal->isEmpty()) {
            return 'Belum diatur';
        }

        if ($jadwal->every(fn (JamOperasionalUmkm $item) => $item->is_tutup)) {
            return 'Tutup';
        }

        $semuaHariTerisi = $jadwal->count() === count(JamOperasionalUmkm::HARI);
        $semuaHariBuka = $jadwal->every(fn (JamOperasionalUmkm $item) => ! $item->is_tutup);
        $jamUnik = $jadwal
            ->map(fn (JamOperasionalUmkm $item) => $item->jam_teks)
            ->unique();

        if ($semuaHariTerisi && $semuaHariBuka && $jamUnik->count() === 1) {
            $jam = $jadwal->first();

            return 'Setiap hari '.$jam->jam_buka->format('H:i').'-'.$jam->jam_tutup->format('H:i');
        }

        return 'Lihat jam operasional';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
