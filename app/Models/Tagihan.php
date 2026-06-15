<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

use App\Models\IuranBulanan;
use App\Services\TagihanService;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tagihan extends Model
{
    use Auditable, HasRtScope, SoftDeletes;

    public const BILLING_GROUP_MANUAL = 'manual';

    protected $fillable = [
        'user_id',
        'rumah_id',
        'rt_id',
        'bulan',
        'tahun',
        'billing_group',
        'judul',
        'transaction_number',
        'total',
        'status',
        'payment_method',
        'verification_status',
        'bukti',
        'note',
        'verification_note',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'verified_by',
        'verified_at',
        'paid_at',
        'dikecualikan_at',
        'dikecualikan_oleh',
        'alasan_dikecualikan',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'dikecualikan_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeForMonth($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public static function billingGroupForIuran(IuranBulanan $iuran): string
    {
        $nama = str($iuran->nama)->lower()->toString();

        if (str_contains($nama, 'kebersihan') || str_contains($nama, 'keamanan')) {
            return 'iuran_rutin';
        }

        return 'iuran_' . $iuran->id;
    }

    public static function titleForGroup($items, string $group): string
    {
        if ($group === 'iuran_rutin') {
            $names = $items->pluck('nama')->values();

            if ($names->contains(fn ($name) => str($name)->lower()->contains('kebersihan'))
                && $names->contains(fn ($name) => str($name)->lower()->contains('keamanan'))) {
                return 'Iuran Kebersihan & Keamanan';
            }

            return $names->first() ?? 'Iuran Rutin';
        }

        return $items->pluck('nama')->join(' + ') ?: 'Tagihan Iuran';
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->judul ?: 'Tagihan Iuran RT';
    }

    public function getPaymentReferenceAttribute(): string
    {
        return $this->transaction_number ?: 'Belum ada nomor transaksi';
    }

    public function getVerificationStatusLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'menunggu' => 'Menunggu Verifikasi',
            'valid' => 'Bukti Valid',
            'ditolak' => 'Bukti Ditolak',
            default => 'Belum Dikirim',
        };
    }

    public static function nextTransactionNumber(): string
    {
        do {
            $number = 'TRX-' . now()->format('Ymd') . '-' . strtoupper(str()->random(6));
        } while (self::where('transaction_number', $number)->exists());

        return $number;
    }

    public function getDueDateAttribute(): Carbon
    {
        return Carbon::create($this->tahun, $this->bulan, 1)->endOfMonth();
    }

    public function isOverdue(): bool
    {
        if ($this->status === 'lunas') {
            return false;
        }

        return now()->startOfDay()->gt($this->due_date);
    }

    public function isDueSoon(): bool
    {
        if ($this->status === 'lunas') {
            return false;
        }

        $now = now()->startOfDay();
        return $now->lte($this->due_date) && $now->diffInDays($this->due_date) <= 5;
    }

    public function isDikecualikan(): bool
    {
        return ! is_null($this->dikecualikan_at);
    }

    public function isInsidental(): bool
    {
        return str_starts_with((string) $this->billing_group, 'insidental_');
    }

    public function getDueStatusLabelAttribute(): string
    {
        if ($this->status === 'lunas') {
            return 'Lunas';
        }

        if ($this->isOverdue()) {
            return 'Overdue';
        }

        if ($this->isDueSoon()) {
            return 'Due Soon';
        }

        return 'Jatuh Tempo';
    }

    public function getDueStatusClassAttribute(): string
    {
        if ($this->status === 'lunas') {
            return 'bg-emerald-100 text-emerald-700';
        }

        if ($this->isOverdue()) {
            return 'bg-rose-100 text-rose-700';
        }

        if ($this->isDueSoon()) {
            return 'bg-amber-100 text-amber-700';
        }

        return 'bg-slate-100 text-slate-700';
    }

    public static function generate(int $bulan, int $tahun, ?User $actor = null): array
    {
        return app(TagihanService::class)->generateForMonth($bulan, $tahun, $actor);
    }

    /**
     * Helper untuk mendapatkan komponen iuran (breakdown)
     */
    public function getIuranComponents()
    {
        return IuranBulanan::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->get()
            ->filter(fn (IuranBulanan $iuran) => self::billingGroupForIuran($iuran) === $this->billing_group)
            ->values();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_transfer' => 'Menunggu Konfirmasi',
            'pending_offline' => 'Bayar Offline',
            'failed' => 'Pembayaran Ditolak',
            'lunas' => 'Lunas',
            default => 'Belum Bayar',
        };
    }
}
