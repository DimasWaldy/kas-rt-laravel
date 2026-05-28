<?php

namespace App\Notifications;

use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TagihanDibayar extends Notification
{
    use Queueable;

    protected $tagihan;

    public function __construct(Tagihan $tagihan)
    {
        $this->tagihan = $tagihan;
    }

    public function via($notifiable)
    {
        return ['database']; // Sementara simpan di DB, bisa tambah 'mail' nanti
    }

    public function toArray($notifiable)
    {
        return [
            'tagihan_id' => $this->tagihan->id,
            'nama_warga' => $this->tagihan->user->name,
            'jumlah' => $this->tagihan->total,
            'metode' => $this->tagihan->payment_method,
            'pesan' => 'Pembayaran baru perlu dikonfirmasi dari ' . $this->tagihan->user->name,
        ];
    }
}
