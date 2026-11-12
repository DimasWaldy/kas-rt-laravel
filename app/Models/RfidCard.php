<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidCard extends Model
{
    protected $fillable = ['user_id', 'uid_hash', 'koperasi_verified_at'];

    protected $hidden = ['uid_hash'];

    protected function casts(): array
    {
        return [
            'koperasi_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashUid(string $uid): string
    {
        // A keyed fingerprint avoids storing a readable card identifier.
        return hash_hmac('sha256', strtoupper($uid), config('app.key'));
    }
}
