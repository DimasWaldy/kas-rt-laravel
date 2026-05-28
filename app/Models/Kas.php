<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kas extends Model
{
    protected $table = 'kas';

    protected $fillable = [
        'user_id',
        'jumlah',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
