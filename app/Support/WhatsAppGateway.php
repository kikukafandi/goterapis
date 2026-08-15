<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppGateway
{
    public function enabled(): bool
    {
        return filled(config('services.whatsapp.url'));
    }

    /** @return array{status: string, phone?: string, qr?: string, error?: string} */
    public function status(): array
    {
        if (! $this->enabled()) {
            return ['status' => 'disabled'];
        }

        try {
            return $this->request()->get('/status')->throw()->json();
        } catch (\Throwable $exception) {
            report($exception);

            return ['status' => 'unavailable', 'error' => 'Gateway WhatsApp tidak dapat dihubungi.'];
        }
    }

    public function send(string $phone, string $message): void
    {
        $this->request()->post('/messages', [
            'to' => $phone,
            'message' => $message,
        ])->throw();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.whatsapp.url'), '/'))
            ->withToken((string) config('services.whatsapp.token'))
            ->acceptJson()
            // whatsapp-web.js berjalan di atas Puppeteer; getNumberId + sendMessage biasa
            // menembus 10 detik saat sesi baru bangun.
            ->timeout(30);
    }
}
