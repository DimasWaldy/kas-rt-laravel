<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidSession extends Model
{
    protected $fillable = [
        'kegiatan_id', 'created_by', 'token_hash', 'expires_at',
        'enrollment_user_id', 'enrollment_expires_at', 'last_seen_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'enrollment_expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
