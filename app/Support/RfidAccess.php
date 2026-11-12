<?php

namespace App\Support;

use App\Models\Kegiatan;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RfidAccess
{
    public static function canManage(User $actor, Kegiatan $kegiatan): bool
    {
        if (! $actor->isAktif() || ! $actor->hasPermission('manage-kegiatan')) {
            return false;
        }

        if ($actor->isGlobalOperator()) {
            return true;
        }

        // No fallback to the first RW for an official without a territorial assignment.
        $rwId = $actor->assignedRwId();
        if (! $rwId || (int) $rwId !== (int) $kegiatan->rw_id) {
            return false;
        }

        return $actor->isRwOfficial()
            || ($kegiatan->rt_id !== null && (int) $kegiatan->rt_id === (int) $actor->rt_id);
    }

    public static function participants(Kegiatan $kegiatan): Builder
    {
        return User::query()->where('status_akun', 'aktif')
            ->inRw($kegiatan->rw_id)
            ->when($kegiatan->rt_id, fn (Builder $q) => $q->where('rt_id', $kegiatan->rt_id));
    }

    public static function canManageKoperasi(User $actor): bool
    {
        return self::canManageScopedPermission($actor, 'manage-koperasi');
    }

    public static function canManageWargaKtp(User $actor): bool
    {
        return self::canManageScopedPermission($actor, 'manage-warga');
    }

    /**
     * Resolve the unique Koperasi RFID session scope and territorial constraints for an operator.
     *
     * @return array{scope_key: string, rw_id: int|null, rt_id: int|null}
     */
    public static function resolveKoperasiScope(User $actor): array
    {
        return self::resolveScope($actor, 'koperasi');
    }

    /**
     * Resolve the unique Warga KTP RFID verification scope and territorial constraints for an operator.
     *
     * @return array{scope_key: string, rw_id: int|null, rt_id: int|null}
     */
    public static function resolveWargaKtpScope(User $actor): array
    {
        return self::resolveScope($actor, 'warga');
    }

    public static function matchesKoperasiScope(User $actor, RfidSession $session): bool
    {
        return $session->scope_type === 'koperasi'
            && self::resolveKoperasiScope($actor)['scope_key'] === $session->scope_key;
    }

    public static function matchesWargaKtpScope(User $actor, RfidSession $session): bool
    {
        return $session->scope_type === 'warga'
            && self::resolveWargaKtpScope($actor)['scope_key'] === $session->scope_key;
    }

    public static function koperasiParticipants(RfidSession $session): Builder
    {
        return self::scopedUsersByAttributes($session->rt_id, $session->rw_id);
    }

    public static function koperasiParticipantsForActor(User $actor): Builder
    {
        $scope = self::resolveKoperasiScope($actor);

        return self::scopedUsersByAttributes($scope['rt_id'], $scope['rw_id']);
    }

    public static function wargaKtpParticipants(RfidSession $session): Builder
    {
        return self::verifiedResidentUsers(self::scopedUsersByAttributes($session->rt_id, $session->rw_id));
    }

    public static function wargaKtpParticipantsForActor(User $actor): Builder
    {
        $scope = self::resolveWargaKtpScope($actor);

        return self::verifiedResidentUsers(self::scopedUsersByAttributes($scope['rt_id'], $scope['rw_id']));
    }

    private static function canManageScopedPermission(User $actor, string $permission): bool
    {
        if (! $actor->isAktif() || ! $actor->hasPermission($permission)) {
            return false;
        }

        if ($actor->isGlobalOperator()) {
            return true;
        }

        if ($actor->isRwOfficial()) {
            return (bool) $actor->assignedRwId();
        }

        return (bool) $actor->rt_id;
    }

    /**
     * @return array{scope_key: string, rw_id: int|null, rt_id: int|null}
     */
    private static function resolveScope(User $actor, string $prefix): array
    {
        if ($actor->isGlobalOperator()) {
            return ['scope_key' => $prefix.':global', 'rw_id' => null, 'rt_id' => null];
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();
            abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

            return ['scope_key' => $prefix.':rw:'.$rwId, 'rw_id' => (int) $rwId, 'rt_id' => null];
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');
        $rwId = $actor->assignedRwId();

        return [
            'scope_key' => $prefix.':rt:'.$actor->rt_id,
            'rw_id' => $rwId ? (int) $rwId : null,
            'rt_id' => (int) $actor->rt_id,
        ];
    }

    private static function scopedUsersByAttributes(?int $rtId, ?int $rwId): Builder
    {
        return User::query()->where('status_akun', 'aktif')
            ->when($rtId, fn (Builder $q) => $q->where('rt_id', $rtId))
            ->when(! $rtId && $rwId, fn (Builder $q) => $q->inRw($rwId));
    }

    private static function verifiedResidentUsers(Builder $query): Builder
    {
        return $query->whereRelation('role', 'name', 'warga')
            ->where(fn (Builder $q) => $q->whereDoesntHave('warga')
                ->orWhereHas('warga', fn (Builder $w) => $w->where('status_verifikasi', 'terverifikasi')));
    }

    /**
     * Issue or rotate a 6-hour RFID device session token for the given scope.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function openSession(string $scopeType, string $scopeKey, User $actor, array $attributes = []): string
    {
        $token = bin2hex(random_bytes(32));

        RfidSession::updateOrCreate(['scope_key' => $scopeKey], [
            ...$attributes,
            'scope_type' => $scopeType,
            'created_by' => $actor->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(6),
            'enrollment_user_id' => null,
            'enrollment_expires_at' => null,
            'last_seen_at' => null,
        ]);

        return $token;
    }

    public static function closeSession(string $scopeKey): void
    {
        RfidSession::where('scope_key', $scopeKey)->update([
            'expires_at' => now(),
            'enrollment_user_id' => null,
            'enrollment_expires_at' => null,
        ]);
    }

    public static function startEnrollment(string $scopeKey, int $userId): void
    {
        $session = RfidSession::where('scope_key', $scopeKey)->where('expires_at', '>', now())->first();
        if (! $session) {
            throw ValidationException::withMessages(['rfid' => 'Buka sesi RFID terlebih dahulu.']);
        }
        if (RfidCard::where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages(['rfid' => 'Cabut kartu lama sebelum mendaftarkan kartu pengganti.']);
        }

        $session->update([
            'enrollment_user_id' => $userId,
            'enrollment_expires_at' => now()->addMinutes(2),
        ]);
    }

    public static function cancelEnrollment(string $scopeKey): void
    {
        RfidSession::where('scope_key', $scopeKey)->update([
            'enrollment_user_id' => null,
            'enrollment_expires_at' => null,
        ]);
    }

    public static function revokeCard(int $userId): void
    {
        RfidCard::where('user_id', $userId)->delete();
    }
}
