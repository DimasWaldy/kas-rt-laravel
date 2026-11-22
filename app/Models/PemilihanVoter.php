<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemilihanVoter extends Model
{
    use HasFactory;

    protected $fillable = [
        'pemilihan_id',
        'user_id',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
