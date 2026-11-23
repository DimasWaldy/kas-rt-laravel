<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RukemBantuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'rt_id',
        'rw_id',
        'amount',
        'recipient_name',
        'recipient_phone',
        'given_at',
        'given_by',
        'proof_path',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'given_at' => 'date',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(RukemClaim::class, 'claim_id');
    }

    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
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
                    $subQuery->where('rw_id', $rwId)
                        ->orWhereHas('claim', fn (Builder $c) => $c->where('user_id', $actor->id));
                })
                : $query->whereHas('claim', fn (Builder $c) => $c->where('user_id', $actor->id));
        }

        if (! $actor->rt_id) {
            return $query->whereHas('claim', fn (Builder $c) => $c->where('user_id', $actor->id));
        }

        return $query->where(function (Builder $subQuery) use ($actor) {
            $subQuery->where('rt_id', $actor->rt_id)
                ->orWhereHas('claim', fn (Builder $c) => $c->where('user_id', $actor->id));
        });
    }
}
