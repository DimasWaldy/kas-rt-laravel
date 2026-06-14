<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surat extends Model
{
    use Auditable, HasFactory, HasRtScope;

    public const TYPES = [
        // Keterangan Kependudukan (hanya RT)
        'domisili' => [
            'label' => 'Surat Keterangan Domisili',
            'requires_rw' => false,
        ],
        'kelahiran' => [
            'label' => 'Surat Keterangan Kelahiran',
            'requires_rw' => false,
        ],
        'kematian' => [
            'label' => 'Surat Keterangan Kematian',
            'requires_rw' => false,
        ],
        'pindah_masuk' => [
            'label' => 'Surat Keterangan Pindah Masuk',
            'requires_rw' => false,
        ],
        'pindah_keluar' => [
            'label' => 'Surat Keterangan Pindah Keluar',
            'requires_rw' => false,
        ],
        'belum_menikah' => [
            'label' => 'Surat Keterangan Belum Menikah',
            'requires_rw' => false,
        ],
        'beda_nama' => [
            'label' => 'Surat Keterangan Beda Nama',
            'requires_rw' => false,
        ],

        // Keterangan Sosial-Ekonomi (butuh RW)
        'tidak_mampu' => [
            'label' => 'Surat Keterangan Tidak Mampu',
            'requires_rw' => true,
        ],
        'keterangan_usaha' => [
            'label' => 'Surat Keterangan Usaha',
            'requires_rw' => true,
        ],
        'penghasilan' => [
            'label' => 'Surat Keterangan Penghasilan',
            'requires_rw' => true,
        ],

        // Pengantar dan Umum (butuh RW)
        'pengantar' => [
            'label' => 'Surat Pengantar Administrasi',
            'requires_rw' => true,
        ],
        'pengantar_nikah' => [
            'label' => 'Surat Pengantar Nikah',
            'requires_rw' => true,
        ],
        'pengantar_skck' => [
            'label' => 'Surat Pengantar SKCK',
            'requires_rw' => true,
        ],
        'pengantar_beasiswa' => [
            'label' => 'Surat Pengantar Beasiswa',
            'requires_rw' => true,
        ],

        // Umum
        'umum' => [
            'label' => 'Surat Keterangan Umum',
            'requires_rw' => false,
        ],
    ];

    protected $fillable = [
        'user_id', 'rt_id', 'surat_number', 'verification_code', 'type', 'subject',
        'purpose', 'content', 'requires_rw', 'status', 'submitted_at',
        'verified_rt_by', 'verified_rt_at', 'approved_rt_by', 'approved_rt_at',
        'verified_rw_by', 'verified_rw_at', 'approved_rw_by', 'approved_rw_at',
        'rejected_by', 'rejected_at', 'rejected_reason', 'result_file',
    ];

    protected function casts(): array
    {
        return [
            'requires_rw' => 'boolean',
            'submitted_at' => 'datetime',
            'verified_rt_at' => 'datetime',
            'approved_rt_at' => 'datetime',
            'verified_rw_at' => 'datetime',
            'approved_rw_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function verifierRt(): BelongsTo { return $this->belongsTo(User::class, 'verified_rt_by'); }
    public function approverRt(): BelongsTo { return $this->belongsTo(User::class, 'approved_rt_by'); }
    public function verifierRw(): BelongsTo { return $this->belongsTo(User::class, 'verified_rw_by'); }
    public function approverRw(): BelongsTo { return $this->belongsTo(User::class, 'approved_rw_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function attachments(): HasMany { return $this->hasMany(SuratAttachment::class); }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? str($this->type)->headline()->toString();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Menunggu Verifikasi RT',
            'verified_rt' => 'Menunggu Persetujuan Ketua RT',
            'approved_rt' => 'Menunggu Verifikasi RW',
            'verified_rw' => 'Menunggu Persetujuan Ketua RW',
            'done' => 'Selesai',
            'rejected' => 'Ditolak',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function isFinal(): bool
    {
        return $this->status === 'done';
    }
}
