<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'rumah_id', 'rt_id', 'is_penanggung_jawab_rumah', 'phone', 'status_akun'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function koperasiMember(): HasOne
    {
        return $this->hasOne(KoperasiMember::class);
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
        return $query->whereHas('rt', function (Builder $query) use ($rwId) {
            $query->where('rw_id', $rwId);
        });
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->canAccessAllRts()) {
            return $query;
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

        return $this->role->permissions()
            ->where('name', $permission)
            ->exists();
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

    public function pengaduans(): \Illuminate\Database\Eloquent\Relations\HasMany
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
        return $this->hasPermission('manage-finance')
            || in_array($this->role_name, ['admin', 'super_admin', 'bendahara', 'bendahara_rt'], true);
    }

    public function canManageWarga(): bool
    {
        return $this->hasPermission('manage-warga')
            || in_array($this->role_name, ['admin', 'super_admin', 'sekretaris', 'sekretaris_rt'], true);
    }

    public function canManagePengaduan(): bool
    {
        return $this->hasPermission('manage-pengaduan')
            || in_array($this->role_name, ['admin', 'super_admin', 'sekretaris', 'sekretaris_rt', 'ketua_rt'], true);
    }

    public function canViewFinance(): bool
    {
        return $this->hasPermission('view-finance') || $this->canManageFinance();
    }
}
