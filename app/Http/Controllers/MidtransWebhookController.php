<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MidtransWebhookController extends Controller
{
    /** Terima notifikasi status pembayaran dari Midtrans. */
    public function handle(Request $request): Response
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $serverKey = (string) config('services.midtrans.server_key');

        // Verifikasi keaslian notifikasi (SHA512 sesuai dokumentasi Midtrans).
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        if (! hash_equals($expected, (string) $request->input('signature_key'))) {
            return response('Invalid signature', 403);
        }

        $order = Order::where('code', $orderId)->first();
        if ($order === null) {
            return response('Order not found', 404);
        }

        $transaction = (string) $request->input('transaction_status');
        $fraud = (string) $request->input('fraud_status');

        $paid = $transaction === 'settlement'
            || ($transaction === 'capture' && $fraud !== 'challenge');
        $failed = in_array($transaction, ['deny', 'expire', 'cancel'], true);

        $payment = $order->payment()->firstOrNew([]);
        $payment->fill([
            'gateway' => 'midtrans',
            'gateway_ref' => $orderId,
            'method' => $request->input('payment_type'),
            'amount' => (int) $grossAmount,
            'status' => $paid ? 'paid' : ($failed ? 'failed' : 'pending'),
            'paid_at' => $paid ? now() : null,
            'raw' => $request->all(),
        ])->save();

        // Hanya naikkan status pesanan yang masih menunggu pembayaran.
        if ($paid && $order->status === 'pending_payment') {
            $order->update(['status' => 'paid']);
        }

        return response('OK', 200);
    }
}
