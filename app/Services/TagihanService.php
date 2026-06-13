<?php

namespace App\Services;

use App\Models\IuranBulanan;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\TagihanCreated;
use Illuminate\Support\Facades\Notification;

class TagihanService
{
    public function generateForMonth(int $bulan, int $tahun, ?User $actor = null): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'updated' => 0,
        ];

        $groups = IuranBulanan::forMonth($bulan, $tahun)
            ->get()
            ->groupBy(fn (IuranBulanan $iuran) => Tagihan::billingGroupForIuran($iuran))
            ->filter(fn ($items) => $items->sum('jumlah') > 0);

        if ($groups->isEmpty()) {
            return $result;
        }

        Rumah::with('penanggungJawab')
            ->when($actor, fn ($query) => $query->visibleTo($actor))
            ->where('status', 'aktif')
            ->whereNotNull('penanggung_jawab_id')
            ->get()
            ->each(function (Rumah $rumah) use ($bulan, $tahun, $groups, &$result) {
                $user = $rumah->penanggungJawab;
                if (! $user) {
                    return;
                }

                $groups->each(function ($items, string $group) use ($rumah, $user, $bulan, $tahun, &$result) {
                    $tagihan = Tagihan::firstOrNew([
                        'rumah_id' => $rumah->id,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'billing_group' => $group,
                    ]);

                    $wasNew = ! $tagihan->exists;
                    if (! $wasNew) {
                        $result['skipped']++;
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
                            'Tagihan ' . $tagihan->display_title . ' otomatis dibuat untuk bulan ' . $bulan . ' tahun ' . $tahun
                        );
                    }

                    if ($wasNew && filled($user->phone)) {
                        Notification::send($user, new TagihanCreated($tagihan));
                    }
                });
            });

        return $result;
    }
}
