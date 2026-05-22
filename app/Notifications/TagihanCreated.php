<?php

namespace App\Notifications;

use App\Models\Tagihan;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TagihanCreated extends Notification
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
            ->subject('Tagihan Baru: ' . $this->tagihan->getDueDateAttribute()->translatedFormat('F Y'))
            ->line("Tagihan RT baru telah dibuat untuk Anda.")
            ->line("Periode: {$this->tagihan->bulan}/{$this->tagihan->tahun}")
            ->line('Total tagihan: Rp ' . number_format($this->tagihan->total, 0, ',', '.'))
            ->line('Tanggal jatuh tempo: ' . $this->tagihan->due_date->format('d F Y'))
            ->line('Silakan lakukan pembayaran sebelum jatuh tempo.');
    }

    public function toWhatsapp(object $notifiable): array
    {
        return [
            'body' => "Halo {$notifiable->name}, tagihan iuran RT untuk periode {$this->tagihan->bulan}/{$this->tagihan->tahun} telah dibuat. Total tagihan: Rp " . number_format($this->tagihan->total, 0, ',', '.') . ". Jatuh tempo pada " . $this->tagihan->due_date->format('d F Y') . ".",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tagihan_id' => $this->tagihan->id,
            'message' => 'Tagihan baru berhasil dibuat dan dikirim ke notifikasi Anda.',
            'bulan' => $this->tagihan->bulan,
            'tahun' => $this->tagihan->tahun,
            'total' => $this->tagihan->total,
        ];
    }
}
