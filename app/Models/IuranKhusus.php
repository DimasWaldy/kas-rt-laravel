<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IuranKhusus extends Model
{
    protected $table = 'iuran_khusus';

    protected $fillable = [
        'rt_id',
        'created_by',
        'jenis',
        'judul',
        'keterangan',
        'nominal_per_warga',
        'billing_group',
        'tanggal_kejadian',
        'total_tagihan',
        'total_terkumpul',
    ];

    protected function casts(): array
    {
        return [
            'nominal_per_warga' => 'integer',
            'total_terkumpul' => 'integer',
            'tanggal_kejadian' => 'date',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'billing_group', 'billing_group');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'kematian' => 'Iuran Kematian',
            'pembangunan' => 'Iuran Pembangunan',
            'sosial' => 'Iuran Sosial',
            'kegiatan' => 'Iuran Kegiatan',
            'lainnya' => 'Iuran Lainnya',
            default => str($this->jenis)->headline()->toString(),
        };
    }

    public function getJenisColorAttribute(): string
    {
        return match ($this->jenis) {
            'kematian' => 'bg-slate-50 text-slate-700 border-slate-200',
            'pembangunan' => 'bg-amber-50 text-amber-700 border-amber-200',
            'sosial' => 'bg-blue-50 text-blue-700 border-blue-200',
            'kegiatan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'lainnya' => 'bg-purple-50 text-purple-700 border-purple-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    public function getTotalDikecualikanAttribute(): int
    {
        return $this->tagihans()->whereNotNull('dikecualikan_at')->count();
    }

    public function getTotalBelumBayarAttribute(): int
    {
        return $this->tagihans()
            ->where('status', 'belum_bayar')
            ->whereNull('dikecualikan_at')
            ->count();
    }
}
