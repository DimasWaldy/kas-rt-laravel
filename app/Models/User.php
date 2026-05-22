<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'no_kk', 'is_kepala_keluarga', 'jumlah_anggota_keluarga', 'phone', 'rt', 'rw'])]
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
            'jumlah_anggota_keluarga' => 'integer',
            'role_id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }

    public function isAdmin(): bool
    {
        return $this->role_name === 'admin';
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
}
