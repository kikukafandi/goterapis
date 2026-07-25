<?php

namespace App\Support\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Midtrans Snap: buat transaksi, kembalikan URL halaman pembayaran.
 * Status lunas dikonfirmasi lewat webhook (MidtransWebhookController), bukan di sini.
 */
class MidtransGateway implements PaymentGateway
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function pay(Order $order): ?string
    {
        $order->payment()->updateOrCreate(
            ['order_id' => $order->id],
            ['gateway' => 'midtrans', 'gateway_ref' => $order->code, 'amount' => $order->total, 'status' => 'pending'],
        );

        $snap = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $order->code, // dipakai mencocokkan di webhook
                'gross_amount' => $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'callbacks' => [
                'finish' => route('pesanan.show', $order),
            ],
        ]);

        return $snap->redirect_url;
    }
}
