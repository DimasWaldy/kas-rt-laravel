<?php

namespace App\Services;

use App\Models\IuranBulanan;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\TagihanCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TagihanService
{
    public function generateForMonth(int $bulan, int $tahun, ?User $actor = null): array
    {
        return DB::transaction(function () use ($bulan, $tahun, $actor) {
            IuranBulanan::query()->where('bulan', $bulan)->where('tahun', $tahun)
                ->orderBy('id')->lockForUpdate()->get();

            return $this->generateLockedMonth($bulan, $tahun, $actor);
        }, 3);
    }

    private function generateLockedMonth(int $bulan, int $tahun, ?User $actor): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'updated' => 0,
            'active_houses' => 0,
            'has_iuran' => false,
            'unbilled_houses' => [],
        ];

        $groups = IuranBulanan::forMonth($bulan, $tahun)
            ->get()
            ->groupBy(fn (IuranBulanan $iuran) => Tagihan::billingGroupForIuran($iuran))
            ->filter(fn ($items) => $items->sum('jumlah') > 0);

        $result['has_iuran'] = $groups->isNotEmpty();

        $activeGroups = $groups->keys()->all();
        $orphanedBills = Tagihan::forMonth($bulan, $tahun)
            ->whereNotNull('billing_group')
            ->whereNotIn('billing_group', $activeGroups ?: ['__no_active_groups__'])
            ->whereIn('status', ['belum_bayar', 'failed'])
            ->whereNotIn('verification_status', ['menunggu', 'valid']);

        if ($actor && ! $actor->isGlobalOperator()) {
            $orphanedBills->whereHas('rumah', fn ($query) => $query->billingVisibleTo($actor));
        }

        $orphanedBills->delete();

        Rumah::with('penanggungJawab')
            ->billingVisibleTo($actor)
            ->where('status', 'aktif')
            ->orderBy('id')
            ->get()
            ->each(function (Rumah $rumah) use ($bulan, $tahun, $groups, &$result) {
                $result['active_houses']++;
                if ($reason = $rumah->billingIssue()) {
                    $result['unbilled_houses'][] = ['id' => $rumah->id, 'kode_rumah' => $rumah->kode_rumah, 'reason' => $reason];

                    return;
                }
                $user = $rumah->penanggungJawab;
                if ($groups->isEmpty()) {
                    return;
                }

                $groups->each(function ($items, string $group) use ($rumah, $user, $bulan, $tahun, &$result) {
                    $tagihan = Tagihan::query()
                        ->where('rumah_id', $rumah->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->where('billing_group', $group)
                        ->lockForUpdate()
                        ->first() ?? new Tagihan([
                            'rumah_id' => $rumah->id,
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                            'billing_group' => $group,
                        ]);

                    $wasNew = ! $tagihan->exists;
                    if (! $wasNew) {
                        $result['skipped']++;
                        if (! in_array($tagihan->status, ['belum_bayar', 'failed'], true)) {
                            return;
                        }
                    }

                    $oldValues = $tagihan->getOriginal();

                    $tagihan->user_id = $user->id;
                    $tagihan->rt_id = $rumah->rt_id ?? $user->rt_id;
                    $tagihan->judul = Tagihan::titleForGroup($items, $group);
                    if ($wasNew) {
                        $tagihan->total = (int) $items->sum('jumlah');
                        $tagihan->status = 'belum_bayar';
                        $tagihan->payment_method = 'none';
                        $tagihan->verification_status = 'belum_dikirim';
                        $tagihan->transaction_number = null;
                        $tagihan->bukti = null;
                        $tagihan->note = null;
                        $tagihan->verification_note = null;
                        $tagihan->rejection_reason = null;
                        $tagihan->rejected_at = null;
                        $tagihan->rejected_by = null;
                        $tagihan->verified_by = null;
                        $tagihan->verified_at = null;
                        $tagihan->paid_at = null;
                    } elseif (in_array($tagihan->status, ['belum_bayar', 'failed'], true)) {
                        $tagihan->total = (int) $items->sum('jumlah');

                        if ($tagihan->verification_status !== 'ditolak') {
                            $tagihan->payment_method = 'none';
                            $tagihan->verification_status = 'belum_dikirim';
                            $tagihan->transaction_number = null;
                            $tagihan->bukti = null;
                            $tagihan->note = null;
                            $tagihan->verification_note = null;
                            $tagihan->rejection_reason = null;
                            $tagihan->rejected_at = null;
                            $tagihan->rejected_by = null;
                            $tagihan->verified_by = null;
                            $tagihan->verified_at = null;
                            $tagihan->paid_at = null;
                        }
                    }

                    $tagihan->saveQuietly();

                    if ($wasNew) {
                        $result['created']++;
                    } elseif ($tagihan->wasChanged()) {
                        $result['updated']++;
                    }

                    if ($wasNew || $tagihan->wasChanged()) {
                        $tagihan->recordAuditWithNote(
                            'tagihan_generated',
                            $wasNew ? [] : $oldValues,
                            'Tagihan '.$tagihan->display_title.' otomatis dibuat untuk bulan '.$bulan.' tahun '.$tahun
                        );
                    }

                    if ($wasNew && $user) {
                        Notification::send($user, new TagihanCreated($tagihan));
                    }
                });
            });

        return $result;
    }
}
