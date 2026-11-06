<?php

namespace App\Models\Concerns;

use App\Models\Rt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRtScope
{
    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId
                ? $query->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', $rwId))
                : $query->whereRaw('1 = 0');
        }

        if (! $actor->rt_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->getModel()->qualifyColumn('rt_id'), $actor->rt_id);
    }

    public function isVisibleTo(User $actor): bool
    {
        if ($actor->isGlobalOperator()) {
            return true;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return (bool) ($rwId && $this->rt()->where('rw_id', $rwId)->exists());
        }

        return (bool) ($actor->rt_id && $this->rt_id === $actor->rt_id);
    }
}
