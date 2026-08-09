<?php

namespace App\Jobs;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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
        $payment = $order->payment;

        if ($payment?->status === 'refunded') {
            return;
        }
        if ($payment === null || $payment->status !== 'paid' || $payment->refund_amount !== $this->amount) {
            throw new \RuntimeException('Pengembalian dana tidak lagi valid.');
        }

        $payment->increment('refund_attempts');

        try {
            $gateway->refund($order, $this->amount);
            $payment->refresh()->update([
                'refunded_at' => now(),
                'refund_failed_at' => null,
                'refund_error' => null,
            ]);
            $order->changeStatus('refunded', 'Dana pembayaran telah dikembalikan.', from: ['cancelled', 'disputed']);
        } catch (\Throwable $exception) {
            $payment->update([
                'refund_failed_at' => now(),
                'refund_error' => str($exception->getMessage())->limit(1000),
            ]);
            Log::error('Pengembalian dana gagal.', ['order_id' => $order->id, 'payment_id' => $payment->id]);

            throw $exception;
        }
    }
}
