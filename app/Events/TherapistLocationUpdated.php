<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TherapistLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public int $distanceM,
        public int $accuracy,
        public string $updatedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("orders.{$this->order->id}")];
    }

    public function broadcastAs(): string
    {
        return 'therapist.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'distance_m' => $this->distanceM,
            'accuracy' => $this->accuracy,
            'updated_at' => $this->updatedAt,
        ];
    }
}
