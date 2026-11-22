<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemilihanKandidat extends Model
{
    use HasFactory;

    protected $fillable = [
        'pemilihan_id',
        'nomor_urut',
        'nama_calon',
        'nama_wakil',
        'visi',
        'misi',
        'slogan',
        'foto_path',
    ];

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class);
    }

    public function suaras()
    {
        return $this->hasMany(PemilihanSuara::class, 'kandidat_id');
    }
}
