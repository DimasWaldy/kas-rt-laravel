<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['surat_id', 'file_path', 'file_name', 'file_type', 'file_size'];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }
}
