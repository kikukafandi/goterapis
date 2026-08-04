<?php

namespace App\Jobs;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefundLatePayment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public function __construct(public int $orderId, public int $amount) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600, 21600, 86400];
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(PaymentGateway $gateway): void
    {
        $order = Order::with('payment')->findOrFail($this->orderId);

        if ($order->payment?->status === 'refunded') {
            return;
        }

        $gateway->refund($order, $this->amount);
    }
}
