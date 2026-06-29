<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuKeluarga extends Model
{
    protected $fillable = [
        'no_kk',
        'rumah_id',
        'nama_kepala_keluarga',
    ];

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
    }

    public function wargas(): HasMany
    {
        return $this->hasMany(Warga::class);
    }
}
