<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengaduan extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'judul',
        'kategori',
        'deskripsi',
        'foto',
        'status',
        'tanggapan',
        'tanggapan_oleh',
        'tanggapan_at',
    ];

    protected $casts = [
        'tanggapan_at' => 'datetime',
    ];

    /**
     * Relasi ke User pembuat pengaduan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User pengurus/admin yang menanggapi pengaduan.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tanggapan_oleh');
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->rt()->value('rw_id');

            return $rwId
                ? $query->where(function (Builder $subQuery) use ($actor, $rwId) {
                    $subQuery->where('user_id', $actor->id)
                        ->orWhereHas('user.rt', fn (Builder $rt) => $rt->where('rw_id', $rwId));
                })
                : $query->where('user_id', $actor->id);
        }

        if (! $actor->rt_id) {
            return $query->where('user_id', $actor->id);
        }

        return $query->where(function (Builder $subQuery) use ($actor) {
            $subQuery->where('user_id', $actor->id)
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('rt_id', $actor->rt_id));
        });
    }

    public function isVisibleTo(User $actor): bool
    {
        if ($actor->isGlobalOperator() || $this->user_id === $actor->id) {
            return true;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->rt()->value('rw_id');

            return (bool) ($rwId && $this->user()->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', $rwId))->exists());
        }

        return (bool) ($actor->rt_id && $this->user()->where('rt_id', $actor->rt_id)->exists());
    }
}
