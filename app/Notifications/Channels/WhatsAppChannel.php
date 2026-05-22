<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);
        if (! $to) {
            Log::warning('WhatsApp notification skipped: no destination phone number. Notifiable class: ' . get_class($notifiable));
            return;
        }

        $message = $notification->toWhatsapp($notifiable);
        if (! is_array($message) || empty($message['body'])) {
            Log::warning('WhatsApp notification skipped: invalid payload.', ['notification' => get_class($notification)]);
            return;
        }

        $apiUrl = config('services.whatsapp.url');
        $apiToken = config('services.whatsapp.token');
        $from = config('services.whatsapp.from');

        if (! $apiUrl || ! $apiToken || ! $from) {
            Log::warning('WhatsApp notification skipped: WhatsApp service not configured.');
            return;
        }

        $payload = array_merge([
            'to' => $to,
            'from' => $from,
            'body' => $message['body'],
        ], $message['extra'] ?? []);

        try {
            Http::withToken($apiToken)
                ->acceptJson()
                ->post($apiUrl, $payload);

            Log::info('WhatsApp notification sent.', ['to' => $to, 'notification' => get_class($notification)]);
        } catch (\Exception $e) {
            Log::error('WhatsApp notification failed.', [
                'to' => $to,
                'notification' => get_class($notification),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
