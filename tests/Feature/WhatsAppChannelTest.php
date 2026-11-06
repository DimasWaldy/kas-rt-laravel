<?php

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('whatsapp tanpa konfigurasi dilewati tanpa http atau pembuatan payload', function ($url, $token, $from) {
    config(['services.whatsapp' => compact('url', 'token', 'from')]);
    Http::fake();
    Log::spy();
    $user = User::factory()->create(['phone' => '081234567890']);
    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return [WhatsAppChannel::class];
        }

        public function toWhatsapp(object $notifiable): array
        {
            throw new RuntimeException('Payload tidak boleh dibuat ketika konfigurasi kosong.');
        }
    };

    $user->notifyNow($notification);

    Http::assertNothingSent();
    Log::shouldHaveReceived('info')->once()->with('WhatsApp notification skipped: WhatsApp service not configured.');
})->with([
    'semua kosong' => [null, null, null],
    'url kosong' => ['', 'test-token', '6281234567890'],
    'token kosong' => ['https://example.test/send', '', '6281234567890'],
    'pengirim kosong' => ['https://example.test/send', 'test-token', ''],
    'whitespace' => [' ', ' ', ' '],
]);
