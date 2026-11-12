<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidCard extends Model
{
    protected $fillable = [
        'user_id',
        'uid_hash',
        'ktp_verified_at',
        'verified_by',
        'verified_rt_id',
        'koperasi_verified_at',
    ];

    protected $hidden = ['uid_hash'];

    protected function casts(): array
    {
        return [
            'ktp_verified_at' => 'datetime',
            'koperasi_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifiedRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'verified_rt_id');
    }

    public function isEligibleForKoperasi(): bool
    {
        return $this->ktp_verified_at !== null || $this->koperasi_verified_at !== null;
    }

    public static function hashUid(string $uid): string
    {
        // A keyed fingerprint avoids storing a readable card identifier.
        return hash_hmac('sha256', strtoupper($uid), config('app.key'));
    }
}
