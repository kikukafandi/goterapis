<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'shop_order_id', 'gateway', 'gateway_ref', 'method', 'amount', 'status', 'paid_at', 'raw', 'refund_amount', 'refund_requested_at', 'refunded_at', 'refund_failed_at', 'refund_error'];

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if (($payment->order_id === null) === ($payment->shop_order_id === null)) {
                throw new \LogicException('Pembayaran harus memiliki tepat satu pemilik pesanan.');
            }
        });
    }

    protected $casts = [
        'raw' => 'array',
        'paid_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_failed_at' => 'datetime',
    ];

    public function canRetryRefund(): bool
    {
        return $this->status === 'paid'
            && $this->refund_amount > 0
            && $this->refund_failed_at !== null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shopOrder(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
