<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WargaTerverifikasi extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pesan' => $notifiable->onboarding_domisili
                ? 'Akun Anda telah diverifikasi pengurus. Silakan lengkapi domisili dari dashboard.'
                : 'Akun Anda telah diverifikasi RT dan sekarang sudah aktif.',
            'url' => '/dashboard',
        ];
    }
}
