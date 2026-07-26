<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('orders.'.$this->message->order_id);
    }

    public function broadcastWith(): array
    {
        return [
            ...$this->message->only(['id', 'body', 'sender_id', 'created_at']),
            'sender_name' => $this->message->sender->name,
        ];
    }
}
