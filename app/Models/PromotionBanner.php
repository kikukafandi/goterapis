<?php

namespace App\Models;

use Database\Factories\PromotionBannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PromotionBanner extends Model
{
    /** @use HasFactory<PromotionBannerFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function imageUrl(): string
    {
        return Storage::url($this->image_path);
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }

        if ($this->starts_at?->isFuture()) {
            return 'Terjadwal';
        }

        if ($this->ends_at?->isPast()) {
            return 'Berakhir';
        }

        return 'Aktif';
    }
}
