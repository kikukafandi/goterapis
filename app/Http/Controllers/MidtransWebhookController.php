<?php

namespace App\Http\Controllers;

use App\Jobs\RefundLatePayment;
use App\Models\Order;
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
            || ($transaction === 'capture' && $fraud !== 'challenge');
        $failed = in_array($transaction, ['deny', 'expire', 'cancel'], true);

        $latePayment = DB::transaction(function () use ($request, $orderId, $grossAmount, $paid, $failed) {
            $order = Order::where('code', $orderId)->lockForUpdate()->first();
            if ($order === null) {
                return 'not_found';
            }
            if ($grossAmount !== number_format((int) $order->total, 2, '.', '')) {
                return 'invalid_amount';
            }

            $payment = $order->payment()->firstOrNew([]);
            $status = $payment->status;
            if (! in_array($status, ['paid', 'refunded'], true)) {
                $status = $paid ? 'paid' : ($failed ? 'failed' : 'pending');
            }
            $payment->fill([
                'gateway' => 'midtrans',
                'gateway_ref' => $orderId,
                'method' => $request->input('payment_type'),
                'amount' => $order->total,
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
                'raw' => $request->all(),
            ])->save();

            if ($paid && $order->status === 'pending_payment') {
                $order->changeStatus('paid', 'Pembayaran pesanan berhasil.');
            }

            return $paid && $order->status === 'cancelled'
                && $order->cancel_reason === 'Pembayaran tidak diselesaikan sampai batas waktu.'
                    ? $order->id
                    : null;
        });

        if ($latePayment === 'not_found') {
            return response('Order not found', 404);
        }
        if ($latePayment === 'invalid_amount') {
            return response('Invalid gross amount', 422);
        }
        if (is_int($latePayment)) {
            RefundLatePayment::dispatch($latePayment, (int) $grossAmount)->afterCommit();
        }

        return response('OK', 200);
    }
}
