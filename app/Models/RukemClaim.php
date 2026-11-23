<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RukemClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rt_id',
        'rw_id',
        'deceased_name',
        'nik_deceased',
        'relationship',
        'death_date',
        'funeral_date',
        'cemetery_location',
        'description',
        'proof_path',
        'status',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'death_date' => 'date',
        'funeral_date' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function bantuan(): HasOne
    {
        return $this->hasOne(RukemBantuan::class, 'claim_id');
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

    /**
     * Scope untuk berita duka yang telah terverifikasi/disetujui untuk siaran lingkungan warga.
     */
    public function scopeBroadcast(Builder $query): Builder
    {
        return $query->whereIn('status', ['verified', 'approved', 'done']);
    }
}
