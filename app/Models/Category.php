<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $guarded = ['id'];

    /** Kategori terurut sesuai susunan yang diatur admin. */
    public function scopeTerurut($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category', 'slug');
    }

    /** URL ikon unggahan admin; null berarti pakai gambar bawaan atau SVG fallback. */
    public function iconUrl(): ?string
    {
        return $this->icon_path ? Storage::url($this->icon_path) : null;
    }

    /** @return array<string, string> slug => nama, untuk filter dan label. */
    public static function daftar(): array
    {
        return static::terurut()->pluck('name', 'slug')->all();
    }
}
