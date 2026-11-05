<?php

namespace App\Support;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
}
