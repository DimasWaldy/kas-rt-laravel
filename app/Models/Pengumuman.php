<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';

    public const CATEGORIES = [
        'umum' => 'Umum',
        'darurat' => 'Darurat',
        'keamanan' => 'Keamanan',
        'kesehatan' => 'Kesehatan',
        'infrastruktur' => 'Infrastruktur',
    ];

    public const PRIORITIES = [
        'normal' => 'Normal',
        'penting' => 'Penting',
        'darurat' => 'Darurat',
    ];

    protected $fillable = [
        'rw_id',
        'rt_id',
        'created_by',
        'judul',
        'isi',
        'kategori',
        'prioritas',
        'is_pinned',
        'is_public',
        'is_published',
        'tanggal_mulai',
        'tanggal_berakhir',
    ];

    protected function casts(): array
    {
        return [
            'rw_id' => 'integer',
            'rt_id' => 'integer',
            'created_by' => 'integer',
            'is_pinned' => 'boolean',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'tanggal_mulai' => 'datetime',
            'tanggal_berakhir' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActiveWindow(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('tanggal_mulai')
                    ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhere('tanggal_berakhir', '>=', now());
            });
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query
            ->activeWindow()
            ->where('is_public', true)
            ->whereHas('rw', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $rwId = $user->assignedRwId();

        if (! $rwId && $user->isGlobalOperator()) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        if (! $rwId) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isGlobalOperator() || $user->isRwOfficial()) {
            return $query->where('rw_id', $rwId);
        }

        if (! $user->rt_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('rw_id', $rwId)
            ->where(function (Builder $q) use ($user) {
                $q->whereNull('rt_id')
                    ->orWhere('rt_id', $user->rt_id);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE prioritas WHEN 'darurat' THEN 1 WHEN 'penting' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function isRtScoped(): bool
    {
        return ! is_null($this->rt_id);
    }

    public function isExpired(): bool
    {
        return $this->tanggal_berakhir !== null && $this->tanggal_berakhir->isPast();
    }

    public function isScheduledForFuture(): bool
    {
        return $this->tanggal_mulai !== null && $this->tanggal_mulai->isFuture();
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_published && ! $this->isExpired() && ! $this->isScheduledForFuture();
    }

    public function getKategoriLabelAttribute(): string
    {
        return self::CATEGORIES[$this->kategori]
            ?? str($this->kategori)->headline()->toString();
    }

    public function getPrioritasLabelAttribute(): string
    {
        return self::PRIORITIES[$this->prioritas]
            ?? str($this->prioritas)->headline()->toString();
    }

    public function getPrioritasBadgeClassAttribute(): string
    {
        return match ($this->prioritas) {
            'darurat' => 'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-300',
            'penting' => 'border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
            default => 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
        };
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->rt?->name ?? 'Lingkup RW';
    }
}
