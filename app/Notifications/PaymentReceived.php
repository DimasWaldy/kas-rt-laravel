<?php

namespace App\Notifications;

use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceived extends Notification
{
    use Queueable;

    protected $tagihan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tagihan $tagihan)
    {
        $this->tagihan = $tagihan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tagihan_id' => $this->tagihan->id,
            'user_name' => $this->tagihan->user->name,
            'bulan' => $this->tagihan->bulan,
            'tahun' => $this->tagihan->tahun,
            'total' => $this->tagihan->total,
            'payment_method' => $this->tagihan->payment_method,
            'message' => "Pembayaran baru dari {$this->tagihan->user->name} untuk iuran periode {$this->tagihan->bulan}/{$this->tagihan->tahun}.",
        ];
    }
}
