<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rumah extends Model
{
    use HasRtScope;

    protected $fillable = [
        'kode_rumah',
        'alamat',
        'rt',
        'rw',
        'rt_id',
        'penanggung_jawab_id',
        'status',
    ];

    public function warga(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function getLabelAttribute(): string
    {
        return trim($this->kode_rumah . ' - ' . $this->alamat, ' -');
    }
}
