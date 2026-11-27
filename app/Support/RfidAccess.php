<?php

namespace App\Support;

use App\Models\GerbangLog;
use App\Models\Kegiatan;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public static function canManageGerbang(User $actor): bool
    {
        return self::canManageScopedPermission($actor, 'manage-fasilitas');
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

    /**
     * Resolve the unique Gerbang RFID session scope and territorial constraints for an operator.
     *
     * @return array{scope_key: string, rw_id: int|null, rt_id: int|null}
     */
    public static function resolveGerbangScope(User $actor): array
    {
        return self::resolveScope($actor, 'gerbang');
    }

    public static function gerbangParticipants(RfidSession $session): Builder
    {
        return self::scopedUsersByAttributes($session->rt_id, $session->rw_id);
    }

    public static function gerbangLogsForActor(User $actor): Builder
    {
        $scope = self::resolveGerbangScope($actor);

        return GerbangLog::query()
            ->when($scope['rt_id'], fn (Builder $q) => $q->where('rt_id', $scope['rt_id']))
            ->when(! $scope['rt_id'] && $scope['rw_id'], fn (Builder $q) => $q->where('rw_id', $scope['rw_id']));
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

    public static function scopedUsersByAttributes(?int $rtId, ?int $rwId): Builder
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
    public static function openSession(
        string $scopeType,
        string $scopeKey,
        User $actor,
        array $attributes = [],
        ?\DateTimeInterface $expiresAt = null,
    ): string {
        $token = bin2hex(random_bytes(32));

        RfidSession::updateOrCreate(['scope_key' => $scopeKey], [
            ...$attributes,
            'scope_type' => $scopeType,
            'created_by' => $actor->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt ?? now()->addHours(6),
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

    public static function extractDeviceToken(Request $request): ?string
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Device-Key')
            ?? $request->header('X-RFID-Token')
            ?? $request->input('device_key')
            ?? $request->input('token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function isValidDeviceKey(?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $configured = config('services.rfid.device_key');
        $fallbacks = config('services.rfid.fallback_keys', [
            'smartrw-rfid-device',
            'GuyubRfid2026_SecretKey_ChangeMe',
        ]);

        $acceptedKeys = array_values(array_unique(array_filter([
            is_string($configured) ? $configured : null,
            ...(is_array($fallbacks) ? $fallbacks : []),
        ])));

        foreach ($acceptedKeys as $key) {
            if (hash_equals($key, $token)) {
                return true;
            }
        }

        return false;
    }

    public static function touchDeviceHeartbeat(?string $deviceId = null, ?string $ip = null): void
    {
        $nowFloat = microtime(true);
        $prevSeen = (float) Cache::get('rfid_device_online_at', 0);

        if ($prevSeen > 0) {
            $delta = $nowFloat - $prevSeen;
            if ($delta >= 0.5 && $delta <= 60.0) {
                Cache::put('rfid_device_hb_interval', $delta, now()->addMinutes(5));
            }
        }

        $resolvedDeviceId = is_string($deviceId) && $deviceId !== '' ? $deviceId : 'esp32-default';
        $meta = [
            'device_id' => $resolvedDeviceId,
            'ip' => is_string($ip) && $ip !== '' ? $ip : null,
            'last_seen_at' => now()->toIso8601String(),
        ];

        Cache::put('rfid_device_online_at', $nowFloat, now()->addMinutes(2));
        Cache::put('rfid_device_online', now()->toIso8601String(), now()->addMinutes(2));
        Cache::put('rfid_device_last_meta', $meta, now()->addMinutes(2));
        Cache::put("rfid_device_heartbeat:{$resolvedDeviceId}", $meta, now()->addMinutes(2));
    }

    public static function maskUid(?string $uid): ?string
    {
        if (! is_string($uid)) {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $uid) ?? '');
        $len = strlen($clean);
        if ($len === 0) {
            return null;
        }

        if ($len >= 6) {
            return substr($clean, 0, 2).'••••'.substr($clean, -2);
        }

        return '••••';
    }

    /**
     * Build a live reader online/scan status snapshot with strict UID masking and scope isolation.
     *
     * @return array{
     *     device_online: bool,
     *     last_seen_sec: float|null,
     *     last_device: array{device_id: string|null, ip: string|null, last_seen_at: string|null}|null,
     *     last_scan: array{
     *         id: string|null,
     *         uid_masked: string|null,
     *         status: string,
     *         type: string,
     *         feedback: string,
     *         user_name: string|null,
     *         scope_type: string|null,
     *         direction: string|null,
     *         open_gate: bool,
     *         message: string|null,
     *         at: string|null,
     *         timestamp: float|null
     *     }|null
     * }
     */
    public static function deviceStatusSnapshot(
        ?int $userId = null,
        ?int $rtId = null,
        ?int $sessionId = null,
        bool $allowGlobalFallback = false,
        bool $includeDeviceMeta = false,
    ): array {
        $lastSeen = (float) Cache::get('rfid_device_online_at', 0);
        $interval = (float) Cache::get('rfid_device_hb_interval', 2.0);
        $timeout = max(8.0, min(15.0, $interval * 2.5 + 2.0));
        $nowFloat = microtime(true);
        $deviceOnline = $lastSeen > 0 && ($nowFloat - $lastSeen) <= $timeout;

        $scanResult = null;
        if ($userId) {
            $scanResult = Cache::get("rfid_scan_result:{$userId}");
        }
        if (! $scanResult && $sessionId) {
            $scanResult = Cache::get("rfid_last_scan_session:{$sessionId}");
        }
        if (! $scanResult && $rtId) {
            $scanResult = Cache::get("rfid_last_scan_rt:{$rtId}");
        }
        if (! $scanResult && $allowGlobalFallback) {
            $scanResult = Cache::get('rfid_last_scan_global');
        }

        $lastScan = null;
        if (is_array($scanResult) && isset($scanResult['timestamp']) && ($nowFloat - (float) $scanResult['timestamp']) <= 30.0) {
            $status = (string) ($scanResult['status'] ?? 'error');
            $lastScan = [
                'id' => $scanResult['id'] ?? null,
                'uid_masked' => $scanResult['uid_masked'] ?? self::maskUid(isset($scanResult['uid']) ? (string) $scanResult['uid'] : null),
                'status' => $status,
                'type' => (string) ($scanResult['type'] ?? $status),
                'feedback' => (string) ($scanResult['feedback'] ?? ($status === 'success' ? 'ok' : 'error')),
                'user_name' => $scanResult['user_name'] ?? null,
                'scope_type' => $scanResult['scope_type'] ?? null,
                'direction' => $scanResult['direction'] ?? null,
                'open_gate' => (bool) ($scanResult['open_gate'] ?? false),
                'message' => $scanResult['message'] ?? null,
                'at' => $scanResult['at'] ?? null,
                'timestamp' => (float) $scanResult['timestamp'],
            ];
        }

        $lastDevice = null;
        if ($includeDeviceMeta) {
            $meta = Cache::get('rfid_device_last_meta');
            if (is_array($meta)) {
                $lastDevice = [
                    'device_id' => $meta['device_id'] ?? null,
                    'ip' => $meta['ip'] ?? null,
                    'last_seen_at' => $meta['last_seen_at'] ?? null,
                ];
            }
        }

        return [
            'device_online' => $deviceOnline,
            'last_seen_sec' => $lastSeen > 0 ? round($nowFloat - $lastSeen, 2) : null,
            'last_device' => $lastDevice,
            'last_scan' => $lastScan,
        ];
    }
}
