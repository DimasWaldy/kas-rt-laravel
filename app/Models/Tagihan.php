<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

use App\Models\AuditLog;
use App\Models\IuranBulanan;
use App\Models\User;
use App\Notifications\TagihanCreated;
use Illuminate\Support\Facades\Notification;

class Tagihan extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id',
        'rumah_id',
        'bulan',
        'tahun',
        'billing_group',
        'judul',
        'total',
        'status',
        'payment_method',
        'bukti',
        'note',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
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

    public static function generateForMonth(int $bulan, int $tahun): void
    {
        $groups = IuranBulanan::forMonth($bulan, $tahun)
            ->get()
            ->groupBy(fn (IuranBulanan $iuran) => self::billingGroupForIuran($iuran))
            ->filter(fn ($items) => $items->sum('jumlah') > 0);

        if ($groups->isEmpty()) {
            return;
        }

        Rumah::with('penanggungJawab')
            ->where('status', 'aktif')
            ->whereNotNull('penanggung_jawab_id')
            ->get()
            ->each(function (Rumah $rumah) use ($bulan, $tahun, $groups) {
                $user = $rumah->penanggungJawab;
                if (! $user) {
                    return;
                }

                $groups->each(function ($items, string $group) use ($rumah, $user, $bulan, $tahun) {
                    $tagihan = self::firstOrNew([
                        'rumah_id' => $rumah->id,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'billing_group' => $group,
                    ]);

                    $wasNew = ! $tagihan->exists;
                    $oldValues = $tagihan->getOriginal();

                    $tagihan->user_id = $user->id;
                    $tagihan->judul = self::titleForGroup($items, $group);
                    $tagihan->total = (int) $items->sum('jumlah');

                    if ($wasNew || $tagihan->status !== 'lunas') {
                        $tagihan->status = 'belum_bayar';
                        $tagihan->payment_method = 'none';
                        $tagihan->bukti = null;
                        $tagihan->note = null;
                        $tagihan->paid_at = null;
                    }

                    $tagihan->save();

                    if ($wasNew || $tagihan->wasChanged()) {
                        AuditLog::create([
                            'user_id' => null,
                            'auditable_type' => self::class,
                            'auditable_id' => $tagihan->id,
                            'event' => 'tagihan_generated',
                            'old_values' => $wasNew ? null : $oldValues,
                            'new_values' => $tagihan->getAttributes(),
                            'notes' => 'Tagihan ' . $tagihan->display_title . ' otomatis dibuat untuk bulan ' . $bulan . ' tahun ' . $tahun,
                        ]);
                    }

                    if ($wasNew && filled($user->phone)) {
                        Notification::send($user, new TagihanCreated($tagihan));
                    }
                });
            });
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
            'lunas' => 'Lunas',
            default => 'Belum Bayar',
        };
    }
}
