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
        if (! $actor->isAktif() || ! $actor->hasPermission('manage-koperasi')) {
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
     * Resolve the unique Koperasi RFID session scope and territorial constraints for an operator.
     *
     * @return array{scope_key: string, rw_id: int|null, rt_id: int|null}
     */
    public static function resolveKoperasiScope(User $actor): array
    {
        if ($actor->isGlobalOperator()) {
            return ['scope_key' => 'koperasi:global', 'rw_id' => null, 'rt_id' => null];
        }

        if ($actor->isRwOfficial()) {
            $rwId = $actor->assignedRwId();
            abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

            return ['scope_key' => 'koperasi:rw:'.$rwId, 'rw_id' => (int) $rwId, 'rt_id' => null];
        }

        abort_unless($actor->rt_id, 403, 'Akun belum terhubung ke RT.');
        $rwId = $actor->assignedRwId();

        return [
            'scope_key' => 'koperasi:rt:'.$actor->rt_id,
            'rw_id' => $rwId ? (int) $rwId : null,
            'rt_id' => (int) $actor->rt_id,
        ];
    }

    public static function matchesKoperasiScope(User $actor, RfidSession $session): bool
    {
        return $session->scope_type === 'koperasi'
            && self::resolveKoperasiScope($actor)['scope_key'] === $session->scope_key;
    }

    public static function koperasiParticipants(RfidSession $session): Builder
    {
        return User::query()->where('status_akun', 'aktif')
            ->when($session->rt_id, fn (Builder $q) => $q->where('rt_id', $session->rt_id))
            ->when(! $session->rt_id && $session->rw_id, fn (Builder $q) => $q->inRw($session->rw_id));
    }

    public static function koperasiParticipantsForActor(User $actor): Builder
    {
        $scope = self::resolveKoperasiScope($actor);

        return User::query()->where('status_akun', 'aktif')
            ->when($scope['rt_id'], fn (Builder $q) => $q->where('rt_id', $scope['rt_id']))
            ->when(! $scope['rt_id'] && $scope['rw_id'], fn (Builder $q) => $q->inRw($scope['rw_id']));
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
