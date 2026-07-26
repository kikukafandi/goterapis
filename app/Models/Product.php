<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'bahan-herbal' => 'Bahan Herbal',
        'produk-herbal' => 'Produk Herbal',
        'minyak-terapi' => 'Minyak Terapi',
        'perlengkapan-terapis' => 'Perlengkapan Terapis',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_promoted' => 'boolean',
            'published_at' => 'datetime',
            'price' => 'integer',
            'stock' => 'integer',
            'weight_grams' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str_starts_with($this->image_path, 'images/') ? asset($this->image_path) : Storage::url($this->image_path);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
