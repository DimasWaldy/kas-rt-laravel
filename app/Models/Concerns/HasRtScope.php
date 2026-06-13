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
        if ($actor->canAccessAllRts()) {
            return $query;
        }

        return $query->where($query->getModel()->qualifyColumn('rt_id'), $actor->rt_id);
    }

    public function isVisibleTo(User $actor): bool
    {
        return $actor->canAccessAllRts() || $this->rt_id === $actor->rt_id;
    }
}
