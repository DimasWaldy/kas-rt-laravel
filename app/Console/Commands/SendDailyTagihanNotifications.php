<?php

namespace App\Console\Commands;

use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\DailyTagihanSummary;
use App\Notifications\TagihanReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDailyTagihanNotifications extends Command
{
    protected $signature = 'tagihan:daily-notification';
    protected $description = 'Kirim notifikasi tagihan harian ke kepala keluarga dan ringkasan ke admin.';

    public function handle()
    {
        $tagihans = Tagihan::where('status', '!=', 'lunas')
            ->with('user')
            ->get();

        $dueSoon = $tagihans->filter(fn($tagihan) => $tagihan->isDueSoon());
        $overdue = $tagihans->filter(fn($tagihan) => $tagihan->isOverdue());

        $reminderTagihans = $dueSoon->merge($overdue)->unique('id');
        $reminderTagihans->each(function (Tagihan $tagihan) {
            $user = $tagihan->user;
            if (! $user || ! filled($user->phone)) {
                return;
            }

            Notification::send($user, new TagihanReminder($tagihan));
        });

        $unpaidCount = $tagihans->count();
        $overdueCount = $overdue->count();
        $dueSoonCount = $dueSoon->count();

        $admins = User::whereRelation('role', 'name', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new DailyTagihanSummary($unpaidCount, $overdueCount, $dueSoonCount));
        }

        $this->info("Daily tagihan notification completed: {$unpaidCount} unpaid, {$overdueCount} overdue, {$dueSoonCount} due soon.");
    }
}
