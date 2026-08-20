<?php

namespace App\Support\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\ShopOrder;

/**
 * Gateway simulasi: langsung menandai lunas tanpa jaringan.
 * Dipakai saat kredensial Midtrans belum diisi (dev/test).
 */
class SimulatedGateway implements PaymentGateway
{
    public function pay(Order|ShopOrder $order): ?string
    {
        $order->payment()->firstOrCreate([], [
            'gateway' => 'simulasi',
            'gateway_ref' => $order->code,
            'method' => 'simulasi',
            'amount' => $order->total,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return null; // lunas seketika, tak perlu redirect
    }

    public function refund(Order $order, int $amount): void
    {
        $payment = $order->payment;
        if ($payment === null || $payment->gateway !== 'simulasi' || $payment->status !== 'paid' || $amount <= 0 || $amount > $payment->amount) {
            throw new \RuntimeException('Pembayaran tidak dapat dikembalikan.');
        }

        $payment->update(['status' => 'refunded']);
    }
}
