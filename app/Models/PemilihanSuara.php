<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemilihanSuara extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pemilihan_id',
        'kandidat_id',
        'cast_at',
    ];

    protected $casts = [
        'cast_at' => 'datetime',
    ];

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class);
    }

    public function kandidat()
    {
        return $this->belongsTo(PemilihanKandidat::class, 'kandidat_id');
    }
}
