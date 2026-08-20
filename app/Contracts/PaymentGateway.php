<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\ShopOrder;

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
    public function pay(Order|ShopOrder $order): ?string;

    public function refund(Order $order, int $amount): void;
}
