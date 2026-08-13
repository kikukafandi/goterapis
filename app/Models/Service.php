<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeAvailableTo(Builder $query, ?string $gender): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $query) use ($gender): void {
                $query->whereNull('allowed_gender');
                if ($gender !== null) {
                    $query->orWhere('allowed_gender', $gender);
                }
            });
    }

    public function isAvailableTo(?string $gender): bool
    {
        return $this->is_active && ($this->allowed_gender === null || $this->allowed_gender === $gender);
    }

    public function therapists(): BelongsToMany
    {
        return $this->belongsToMany(TherapistProfile::class, 'therapist_service')
            ->withPivot(['price', 'duration_min'])
            ->withTimestamps();
    }
}
