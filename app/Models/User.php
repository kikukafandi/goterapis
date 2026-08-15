<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'google_id', 'role', 'phone', 'avatar_path', 'tutorial_seen_at', 'legal_version', 'legal_accepted_at'])]
#[Hidden(['password', 'remember_token', 'email', 'phone'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'tutorial_seen_at' => 'datetime',
            'legal_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ponytail: canAccessPanel() + implements FilamentUser ditambahkan saat Filament terinstall (butuh ext-intl)

    public function isTherapist(): bool
    {
        return $this->role === 'therapist';
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $this->phone);

        if (str_starts_with($phone, '08')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62'.$phone;
        }

        return preg_match('/^62\d{8,13}$/', $phone) ? $phone : null;
    }

    /** URL avatar: URL penuh (data demo) atau file lokal WebP (unggahan asli). */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return str_starts_with($this->avatar_path, 'http')
            ? $this->avatar_path
            : Storage::url($this->avatar_path);
    }

    public function therapistProfile(): HasOne
    {
        return $this->hasOne(TherapistProfile::class);
    }
}
