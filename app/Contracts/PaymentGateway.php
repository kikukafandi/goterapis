<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * Seam pembayaran. Simulasi menandai lunas seketika (kembalikan null);
 * Midtrans membuat transaksi Snap & mengembalikan URL redirect, lunas dikonfirmasi via webhook.
 */
interface PaymentGateway
{
    /**
     * Mulai pembayaran untuk pesanan.
     * Kembalikan URL redirect gateway, atau null bila sudah lunas seketika (simulasi).
     */
    public function pay(Order $order): ?string;
}
