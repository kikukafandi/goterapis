<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private PaymentGateway $gateway) {}

    /** Mulai pembayaran pesanan. */
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        $result = DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'pending_payment') {
                return ['error' => 'Pesanan ini tidak menunggu pembayaran.'];
            }
            if ($order->paymentExpired()) {
                return ['error' => 'Batas waktu pembayaran sudah lewat. Silakan pesan lagi.'];
            }

            $redirect = $this->gateway->pay($order);
            if ($redirect === null && ! $order->changeStatus('paid', 'Pembayaran pesanan berhasil.', from: ['pending_payment'])) {
                return ['error' => 'Status pesanan sudah berubah. Muat ulang halaman.'];
            }

            return ['redirect' => $redirect];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }
        if ($result['redirect'] !== null) {
            return redirect()->away($result['redirect']);
        }

        return redirect()->route('pesanan.show', $order)->with('success', 'Pembayaran berhasil. Menunggu terapis menerima pesanan.');
    }
}
