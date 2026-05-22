<?php

namespace App\Notifications;

use App\Models\Tagihan;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TagihanReminder extends Notification
{
    use Queueable;

    protected Tagihan $tagihan;

    public function __construct(Tagihan $tagihan)
    {
        $this->tagihan = $tagihan;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', WhatsAppChannel::class];

        if (filled($notifiable->email) && config('mail.default')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat Tagihan RT: ' . $this->tagihan->getDueDateAttribute()->translatedFormat('F Y'))
            ->line('Tagihan Anda masih belum dibayar.')
            ->line("Periode: {$this->tagihan->bulan}/{$this->tagihan->tahun}")
            ->line('Total tagihan: Rp ' . number_format($this->tagihan->total, 0, ',', '.'))
            ->line('Segera lakukan pembayaran sebelum atau sesegera mungkin.');
    }

    public function toWhatsapp(object $notifiable): array
    {
        $statusLabel = $this->tagihan->isOverdue() ? 'TELAT BAYAR' : 'MENDEKATI JATUH TEMPO';

        return [
            'body' => "Halo {$notifiable->name}, pengingat tagihan RT Anda {$statusLabel} untuk periode {$this->tagihan->bulan}/{$this->tagihan->tahun}. Total: Rp " . number_format($this->tagihan->total, 0, ',', '.') . ". Jatuh tempo: " . $this->tagihan->due_date->format('d F Y') . ".",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tagihan_id' => $this->tagihan->id,
            'message' => 'Pengingat tagihan telah dikirim.',
            'status' => $this->tagihan->due_status_label,
            'bulan' => $this->tagihan->bulan,
            'tahun' => $this->tagihan->tahun,
        ];
    }
}
