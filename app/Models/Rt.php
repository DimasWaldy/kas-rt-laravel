<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rt extends Model
{
    protected $fillable = [
        'rw_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rw_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rumahs(): HasMany
    {
        return $this->hasMany(Rumah::class);
    }
}
