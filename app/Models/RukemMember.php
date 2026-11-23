<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RukemMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rt_id',
        'rw_id',
        'member_number',
        'joined_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function iurans(): HasMany
    {
        return $this->hasMany(RukemIuran::class, 'user_id', 'user_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RukemClaim::class, 'user_id', 'user_id');
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId
                ? $query->where(function (Builder $subQuery) use ($actor, $rwId) {
                    $subQuery->where('user_id', $actor->id)
                        ->orWhere('rw_id', $rwId)
                        ->orWhereHas('user', fn (Builder $u) => $u->inRw($rwId));
                })
                : $query->where('user_id', $actor->id);
        }

        if (! $actor->rt_id) {
            return $query->where('user_id', $actor->id);
        }

        return $query->where(function (Builder $subQuery) use ($actor) {
            $subQuery->where('user_id', $actor->id)
                ->orWhere('rt_id', $actor->rt_id)
                ->orWhereHas('user', fn (Builder $u) => $u->where('rt_id', $actor->rt_id));
        });
    }
}
