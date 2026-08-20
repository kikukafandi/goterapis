<?php

namespace App\Http\Controllers;

use App\Jobs\RefundLatePayment;
use App\Models\Order;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    /** Terima notifikasi status pembayaran dari Midtrans. */
    public function handle(Request $request): Response
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return response('Midtrans is not configured', 503);
        }

        // Verifikasi keaslian notifikasi (SHA512 sesuai dokumentasi Midtrans).
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        if (! hash_equals($expected, (string) $request->input('signature_key'))) {
            return response('Invalid signature', 403);
        }

        $transaction = (string) $request->input('transaction_status');
        $fraud = (string) $request->input('fraud_status');
        $paid = $transaction === 'settlement'
            || ($transaction === 'capture' && $fraud === 'accept');
        $failed = in_array($transaction, ['deny', 'expire', 'cancel'], true);

        $latePayment = DB::transaction(function () use ($request, $orderId, $grossAmount, $transaction, $paid, $failed) {
            $order = str_starts_with($orderId, 'GT-SHOP-')
                ? ShopOrder::where('code', $orderId)->lockForUpdate()->first()
                : Order::where('code', $orderId)->lockForUpdate()->first();
            if ($order === null) {
                return 'not_found';
            }
            if ($grossAmount !== number_format((int) $order->total, 2, '.', '')) {
                return 'invalid_amount';
            }

            $payment = $order->payment()->lockForUpdate()->first();
            if ($payment === null || $payment->gateway !== 'midtrans' || $payment->status === 'refunded') {
                return 'payment_not_found';
            }
            $status = $payment->status === 'paid' ? 'paid' : ($paid ? 'paid' : ($transaction === 'expire' ? 'expired' : ($failed ? 'failed' : 'pending')));
            $payment->fill([
                'gateway' => 'midtrans',
                'gateway_ref' => (string) ($request->input('transaction_id') ?: $orderId),
                'method' => $request->input('payment_type'),
                'amount' => $order->total,
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
                'raw' => $request->only(['transaction_status', 'fraud_status', 'status_code', 'payment_type', 'transaction_time']),
            ])->save();

            if ($paid && $order instanceof ShopOrder) {
                ShopOrder::whereKey($order)->where('status', 'pending_payment')->update(['status' => 'paid', 'paid_at' => $order->paid_at ?? now(), 'updated_at' => now()]);
            } elseif ($paid) {
                $order->changeStatus('paid', 'Pembayaran pesanan berhasil.', from: ['pending_payment']);
            }

            if ($order instanceof ShopOrder) {
                return null;
            }

            if ($paid && $order->status === 'cancelled'
                && $order->cancel_reason === 'Pembayaran tidak diselesaikan sampai batas waktu.'
                && $payment->status !== 'refunded'
                && $payment->refund_requested_at === null) {
                $payment->update([
                    'refund_amount' => $order->total,
                    'refund_requested_at' => $payment->refund_requested_at ?? now(),
                ]);

                return $order->id;
            }

            return null;
        });

        if ($latePayment === 'not_found') {
            return response('Order not found', 404);
        }
        if ($latePayment === 'invalid_amount') {
            return response('Invalid gross amount', 422);
        }
        if ($latePayment === 'payment_not_found') {
            return response('Payment intent not found', 404);
        }
        if (is_int($latePayment)) {
            RefundLatePayment::dispatch($latePayment, (int) $grossAmount)->afterCommit();
        }

        return response('OK', 200);
    }
}
