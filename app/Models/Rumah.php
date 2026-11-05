<?php

namespace App\Models;

use App\Models\Concerns\HasRtScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeBillingVisibleTo(Builder $query, ?User $actor): Builder
    {
        if (! $actor || $actor->isGlobalOperator()) {
            return $query;
        }
        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId ? $query->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', $rwId)) : $query->whereRaw('1 = 0');
        }

        return $actor->rt_id ? $query->where('rumahs.rt_id', $actor->rt_id) : $query->whereRaw('1 = 0');
    }

    public function scopeNeedsBillingContact(Builder $query): Builder
    {
        return $query->where('rumahs.status', 'aktif')->whereDoesntHave('penanggungJawab', function (Builder $user) {
            $user->where('status_akun', 'aktif')->where('is_penanggung_jawab_rumah', true)
                ->whereColumn('users.rumah_id', 'rumahs.id')
                ->where(fn (Builder $rt) => $rt->whereColumn('users.rt_id', 'rumahs.rt_id')
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('users.rt_id')->whereNull('rumahs.rt_id')));
        });
    }

    public function billingIssue(): ?string
    {
        if ($this->status !== 'aktif') {
            return 'Rumah kosong atau nonaktif tidak menerima tagihan baru.';
        }
        $pj = $this->penanggungJawab;
        if (! $pj) {
            return 'Penanggung jawab iuran belum ditetapkan.';
        }
        if (! $pj->isAktif() || ! $pj->is_penanggung_jawab_rumah
            || (int) $pj->rumah_id !== (int) $this->id || (int) $pj->rt_id !== (int) $this->rt_id) {
            return 'Penanggung jawab iuran perlu diperbarui: akun harus aktif dan terdaftar sebagai PJ di rumah serta RT ini.';
        }

        return null;
    }
}
