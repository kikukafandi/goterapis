<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TherapistProfile extends Model
{
    protected $guarded = ['id'];

    /** URL memakai slug, bukan id (tidak membocorkan & bisa ditebak). */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $profile) {
            if (blank($profile->slug)) {
                $name = $profile->user?->name ?? User::find($profile->user_id)?->name ?? 'terapis';
                $profile->slug = Str::slug($name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    protected $casts = [
        'serves_call' => 'boolean',
        'serves_place' => 'boolean',
        'is_featured' => 'boolean',
        'is_available' => 'boolean',
        'schedule_configured' => 'boolean',
        'place_photos' => 'array',
        'facilities' => 'array',
        'place_lat' => 'float',
        'place_lng' => 'float',
        'service_lat' => 'float',
        'service_lng' => 'float',
        'rating_avg' => 'float',
    ];

    /** Label status verifikasi untuk badge segel-daun. */
    public const STATUS_LABELS = [
        'anggota' => 'Anggota Komunitas',
        'identitas' => 'Identitas Terverifikasi',
        'berpengalaman' => 'Terapis Berpengalaman',
        'terdaftar' => 'Terapis Terdaftar',
        'pilihan' => 'Terapis Pilihan',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->verification_status] ?? $this->verification_status;
    }

    public function isEligible(): bool
    {
        return $this->verification_status !== 'anggota'
            && ! $this->documents()->whereIn('type', TherapistDocument::REQUIRED_TYPES)->where('status', '!=', 'approved')->exists();
    }

    /**
     * Pelanggan hanya boleh memesan terapis sesama jenis. Pemesan yang jenis kelaminnya
     * belum terisi (atau tamu) dibiarkan lewat di sini — middleware `gender` yang memaksanya.
     */
    public function isBookableBy(?User $user): bool
    {
        // Terapis tak bisa memesan dirinya sendiri — sekalian menyembunyikan tombolnya di profil.
        if ($user?->id === $this->user_id) {
            return false;
        }

        return $user?->gender === null || $user->gender === $this->gender;
    }

    public function scopeEligible($query)
    {
        return $query->where('verification_status', '!=', 'anggota')
            ->whereDoesntHave('documents', fn ($query) => $query
                ->whereIn('type', TherapistDocument::REQUIRED_TYPES)
                ->where('status', '!=', 'approved'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'therapist_service')
            ->withPivot(['price', 'duration_min'])
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TherapistDocument::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
