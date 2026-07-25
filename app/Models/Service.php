<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function therapists(): BelongsToMany
    {
        return $this->belongsToMany(TherapistProfile::class, 'therapist_service')
            ->withPivot(['price', 'duration_min'])
            ->withTimestamps();
    }
}
