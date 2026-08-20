<?php

namespace App\Support\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Http;
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

    public function pay(Order|ShopOrder $order): ?string
    {
        $foreignKey = $order instanceof ShopOrder ? 'shop_order_id' : 'order_id';
        $order->payment()->updateOrCreate(
            [$foreignKey => $order->id],
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
                'finish' => $order instanceof ShopOrder ? route('shop.orders.show', $order) : route('pesanan.show', $order),
            ],
        ]);

        return $snap->redirect_url;
    }

    public function refund(Order $order, int $amount): void
    {
        $payment = $order->payment;
        if ($payment === null || $payment->status !== 'paid' || $amount <= 0 || $amount > $payment->amount) {
            throw new \RuntimeException('Pembayaran tidak dapat dikembalikan.');
        }
        if ($payment->gateway === 'simulasi') {
            $payment->update(['status' => 'refunded']);

            return;
        }
        if ($payment->gateway !== 'midtrans') {
            throw new \RuntimeException('Gateway pembayaran tidak mendukung pengembalian dana.');
        }

        $baseUrl = config('services.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->post($baseUrl.'/v2/'.rawurlencode((string) $payment->gateway_ref).'/refund', [
                'refund_key' => 'refund-'.$order->code,
                'amount' => $amount,
                'reason' => 'Pembatalan pesanan '.$order->code,
            ])->throw();

        if ((string) $response->json('status_code') !== '200') {
            throw new \RuntimeException('Pengembalian dana Midtrans gagal.');
        }

        $payment->update(['status' => 'refunded']);
    }
}
