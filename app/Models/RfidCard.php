<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidCard extends Model
{
    protected $fillable = ['user_id', 'uid_hash'];

    protected $hidden = ['uid_hash'];

    public static function hashUid(string $uid): string
    {
        // A keyed fingerprint avoids storing a readable card identifier.
        return hash_hmac('sha256', strtoupper($uid), config('app.key'));
    }
}
