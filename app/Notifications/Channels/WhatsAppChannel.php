<?php

namespace App\Notifications\Channels;

use App\Support\WhatsAppGateway;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function __construct(private WhatsAppGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationForWhatsApp();

        if (! $phone || ! $this->gateway->enabled()) {
            return;
        }

        try {
            $this->gateway->send($phone, $notification->toWhatsApp($notifiable));
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi WhatsApp gagal dikirim.', [
                'user_id' => $notifiable->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
