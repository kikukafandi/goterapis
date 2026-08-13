<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapistDocument extends Model
{
    public const REQUIRED_TYPES = ['ktp'];

    protected $guarded = ['id'];

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }
}
