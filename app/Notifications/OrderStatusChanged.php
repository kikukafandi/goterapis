<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $message)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', WhatsAppChannel::class];
    }

    public function toWhatsApp(object $notifiable): string
    {
        $data = $this->toArray($notifiable);

        return "*GoTerapis*\nPesanan {$data['code']}\n{$data['message']}\n\nBuka pesanan: {$data['url']}";
    }

    public function toArray(object $notifiable): array
    {
        $seller = $this->order->therapistProfile()->where('user_id', $notifiable->id)->exists();

        return [
            'order_id' => $this->order->id,
            'code' => $this->order->code,
            'status' => $this->order->status,
            'message' => $this->message,
            'url' => $seller ? route('mitra.pesanan.show', $this->order) : route('pesanan.show', $this->order),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
