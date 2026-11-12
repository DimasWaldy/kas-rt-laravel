<?php

namespace App\Models;

use App\Support\FinancialHistory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'role_id', 'rumah_id', 'rt_id', 'rw_id', 'is_penanggung_jawab_rumah', 'phone', 'status_akun'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->onboarding_domisili && $user->rumah_id) {
                $user->onboarding_domisili = false;
            }
        });
        static::saved(function (User $user) {
            if ($user->wasChanged('rumah_id') && $user->rumah_id) {
                $user->warga()
                    ->where('status_domisili', 'pending')
                    ->update([
                        'status_domisili' => 'dibatalkan',
                        'catatan_domisili' => 'Domisili telah ditetapkan langsung oleh pengurus.',
                    ]);
            }
        });
        static::deleting(function (User $user) {
            if (FinancialHistory::existsForUser($user->id)) {
                throw ValidationException::withMessages([
                    'account' => FinancialHistory::DELETION_MESSAGE,
                ]);
            }
        });
    }

    protected $attributes = [
        'status_akun' => 'aktif',
    ];

    public function routeNotificationForWhatsapp(): ?string
    {
        if (! filled($this->phone)) {
            return null;
        }

        return preg_replace('/[^0-9+]/', '', $this->phone);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_penanggung_jawab_rumah' => 'boolean',
            'role_id' => 'integer',
            'rumah_id' => 'integer',
            'rt_id' => 'integer',
            'rw_id' => 'integer',
            'onboarding_domisili' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function warga(): HasOne
    {
        return $this->hasOne(Warga::class);
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    public function assignedRwId(): ?int
    {
        // RT remains the source for legacy accounts; explicit RW covers officials without an RT.
        return $this->rt_id ? $this->rt()->value('rw_id') : $this->rw_id;
    }

    public function koperasiMember(): HasOne
    {
        return $this->hasOne(KoperasiMember::class);
    }

    public function rfidCard(): HasOne
    {
        return $this->hasOne(RfidCard::class);
    }

    public function koperasiSimpanans(): HasMany
    {
        return $this->hasMany(KoperasiSimpanan::class);
    }

    public function koperasiPinjams(): HasMany
    {
        return $this->hasMany(KoperasiPinjam::class);
    }

    public function scopeInRt(Builder $query, int $rtId): Builder
    {
        return $query->where('rt_id', $rtId);
    }

    public function scopeInRw(Builder $query, int $rwId): Builder
    {
        return $query->where(function (Builder $query) use ($rwId) {
            $query->where(function (Builder $direct) use ($rwId) {
                $direct->whereNull('rt_id')->where('rw_id', $rwId);
            })->orWhereHas('rt', fn (Builder $rt) => $rt->where('rw_id', $rwId));
        });
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isGlobalOperator()) {
            return $query;
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();

            return $rwId ? $query->inRw($rwId) : $query->whereRaw('1 = 0');
        }

        if (! $actor->rt_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('rt_id', $actor->rt_id);
    }

    public function getRtAttribute(mixed $value): Rt|string|null
    {
        if (! $this->rt_id) {
            return $value;
        }

        if (! $this->relationLoaded('rt')) {
            $this->setRelation('rt', $this->rt()->first());
        }

        return $this->getRelation('rt');
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }

    public function isAdmin(): bool
    {
        return $this->isGlobalOperator();
    }

    public function isPendingVerifikasi(): bool
    {
        return $this->status_akun === 'pending_verifikasi';
    }

    public function isAktif(): bool
    {
        return $this->status_akun === 'aktif';
    }

    public function isGlobalOperator(): bool
    {
        return in_array($this->role_name, ['admin', 'super_admin'], true);
    }

    public function isRwOfficial(): bool
    {
        return in_array($this->role_name, ['ketua_rw', 'sekretaris_rw', 'bendahara_rw'], true);
    }

    public function canAccessAllRts(): bool
    {
        return $this->isGlobalOperator() || $this->isRwOfficial();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role) {
            return false;
        }

        $this->loadMissing('role.permissions');

        return $this->role?->permissions
            ->contains('name', $permission) ?? false;
    }

    public function getProfileStatusAttribute(): string
    {
        return empty($this->profileCompletionIssues())
            ? 'Lengkap'
            : 'Belum Lengkap';
    }

    public function profileCompletionIssues(): array
    {
        $issues = [];

        if (blank($this->phone)) {
            $issues[] = 'Nomor HP';
        }

        if (blank($this->warga?->nik)) {
            $issues[] = 'NIK';
        }

        if (blank($this->warga?->kartuKeluarga?->no_kk)) {
            $issues[] = 'Nomor KK';
        }

        if (blank($this->rumah_id)) {
            $issues[] = 'Domisili/Rumah';
        }

        if (blank($this->rt_id)) {
            $issues[] = 'RT';
        }

        if (blank($this->warga?->status_dalam_kk)) {
            $issues[] = 'Status dalam KK';
        }

        return $issues;
    }

    public function pengaduans(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }

    public function canViewSurat(): bool
    {
        return $this->hasPermission('view-surat') || $this->hasPermission('submit-surat');
    }

    public function kas(): HasMany
    {
        return $this->hasMany(Kas::class);
    }

    public function isSekretaris(): bool
    {
        return in_array($this->role_name, ['sekretaris', 'sekretaris_rt'], true);
    }

    public function isBendahara(): bool
    {
        return in_array($this->role_name, ['bendahara', 'bendahara_rt'], true);
    }

    public function canManageFinance(): bool
    {
        return $this->hasPermission('manage-finance');
    }

    public function canManageWarga(): bool
    {
        return $this->hasPermission('manage-warga');
    }

    public function canManagePengaduan(): bool
    {
        return $this->hasPermission('manage-pengaduan');
    }

    public function canViewFinance(): bool
    {
        return $this->hasPermission('view-finance') || $this->canManageFinance();
    }
}
