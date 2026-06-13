<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Rw extends Model
{
    protected $fillable = [
        'name',
        'description',
        'address',
        'kelurahan',
        'kecamatan',
        'kota',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function rts(): HasMany
    {
        return $this->hasMany(Rt::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Rt::class);
    }
}
