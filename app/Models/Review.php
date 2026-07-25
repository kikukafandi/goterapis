<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_hidden' => 'boolean'];

    /** Rata-rata 5 dimensi, untuk tampilan bintang tunggal. */
    public function averageRating(): float
    {
        return round((
            $this->rating_service + $this->rating_punctual + $this->rating_manners
            + $this->rating_hygiene + $this->rating_accuracy
        ) / 5, 2);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }
}
