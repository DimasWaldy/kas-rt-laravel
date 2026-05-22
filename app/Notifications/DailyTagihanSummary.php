<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyTagihanSummary extends Notification
{
    use Queueable;

    protected int $unpaidCount;
    protected int $overdueCount;
    protected int $dueSoonCount;

    public function __construct(int $unpaidCount, int $overdueCount, int $dueSoonCount)
    {
        $this->unpaidCount = $unpaidCount;
        $this->overdueCount = $overdueCount;
        $this->dueSoonCount = $dueSoonCount;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email) && config('mail.default')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ringkasan Tagihan Harian RT')
            ->line('Berikut ringkasan tagihan belum dibayar hari ini:')
            ->line("Total tagihan belum dibayar: {$this->unpaidCount}")
            ->line("Tagihan overdue: {$this->overdueCount}")
            ->line("Tagihan mendekati jatuh tempo: {$this->dueSoonCount}")
            ->line('Silakan cek dashboard admin untuk tindakan selanjutnya.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'unpaid_count' => $this->unpaidCount,
            'overdue_count' => $this->overdueCount,
            'due_soon_count' => $this->dueSoonCount,
            'message' => 'Ringkasan tagihan harian telah dibuat.',
        ];
    }
}
