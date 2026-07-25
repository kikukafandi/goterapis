<?php

namespace App\Support\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;

/**
 * Gateway simulasi: langsung menandai lunas tanpa jaringan.
 * Dipakai saat kredensial Midtrans belum diisi (dev/test).
 */
class SimulatedGateway implements PaymentGateway
{
    public function pay(Order $order): ?string
    {
        $order->payment()->create([
            'gateway' => 'simulasi',
            'gateway_ref' => $order->code,
            'method' => 'simulasi',
            'amount' => $order->total,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return null; // lunas seketika, tak perlu redirect
    }
}
