<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = ['id'];

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
}
