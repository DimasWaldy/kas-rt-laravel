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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'rumah_id', 'no_kk', 'is_kepala_keluarga', 'is_penanggung_jawab_rumah', 'jumlah_anggota_keluarga', 'phone', 'rt', 'rw'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_kepala_keluarga' => 'boolean',
            'is_penanggung_jawab_rumah' => 'boolean',
            'jumlah_anggota_keluarga' => 'integer',
            'role_id' => 'integer',
            'rumah_id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
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
        return $this->role_name === 'admin';
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
        $required = [
            $this->no_kk,
            $this->phone,
            $this->rt,
            $this->rw,
            $this->jumlah_anggota_keluarga,
        ];

        return collect($required)->every(fn($value) => filled($value))
            ? 'Lengkap'
            : 'Belum Lengkap';
    }

    public function pengaduans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function kas(): HasMany
    {
        return $this->hasMany(Kas::class);
    }

    public function isSekretaris(): bool
    {
        return $this->role_name === 'sekretaris';
    }

    public function isBendahara(): bool
    {
        return $this->role_name === 'bendahara';
    }

    public function canManageFinance(): bool
    {
        return $this->hasPermission('manage-finance')
            || in_array($this->role_name, ['admin', 'bendahara']);
    }

    public function canManageWarga(): bool
    {
        return $this->hasPermission('manage-warga')
            || in_array($this->role_name, ['admin', 'sekretaris']);
    }

    public function canManagePengaduan(): bool
    {
        return $this->hasPermission('manage-pengaduan')
            || in_array($this->role_name, ['admin', 'sekretaris']);
    }
}
