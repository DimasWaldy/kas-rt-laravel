<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoperasiMember extends Model
{
    protected $fillable = [
        'user_id',
        'member_number',
        'joined_at',
        'status',
    ];

    protected $casts = [
        'joined_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
