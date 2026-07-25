<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentGateway $gateway) {}

    /** Mulai pembayaran pesanan. */
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        if ($order->status !== 'pending_payment') {
            return back()->with('error', 'Pesanan ini tidak menunggu pembayaran.');
        }
        // Jaga-jaga bila scheduler belum sempat membatalkan (jendelanya sampai satu menit).
        if ($order->paymentExpired()) {
            return back()->with('error', 'Batas waktu pembayaran sudah lewat. Silakan pesan lagi.');
        }

        $redirect = $this->gateway->pay($order);

        // Midtrans → arahkan ke halaman pembayaran; lunas dikonfirmasi via webhook.
        if ($redirect !== null) {
            return redirect()->away($redirect);
        }

        // Simulasi → lunas seketika.
        $order->update(['status' => 'paid']);

        return redirect()->route('pesanan.show', $order)->with('success', 'Pembayaran berhasil. Menunggu terapis menerima pesanan.');
    }
}
