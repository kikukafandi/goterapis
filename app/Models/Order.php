<?php

namespace App\Models;

use App\Notifications\OrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const BLOCKING_STATUSES = ['pending_confirmation', 'pending_payment', 'paid', 'accepted', 'therapist_en_route', 'therapist_arrived', 'in_progress'];

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
    ];

    /** Batas akhir pembayaran: jendela setelah diterima terapis, tak pernah melewati jadwal. */
    public function paymentDeadline(): ?Carbon
    {
        if ($this->accepted_at === null) {
            return null;
        }

        return $this->accepted_at->copy()
            ->addHours((int) config('goterapis.payment_window_hours'))
            ->min($this->scheduled_at);
    }

    /** Sudah diterima terapis tapi lewat batas bayar. */
    public function paymentExpired(): bool
    {
        return $this->status === 'pending_payment' && $this->paymentDeadline()?->isPast() === true;
    }

    /**
     * Batalkan pesanan yang tak kunjung dibayar agar slot terapis terlepas.
     * Belum ada dana masuk pada tahap ini, jadi tidak ada yang perlu dikembalikan.
     *
     * @return int jumlah pesanan yang dibatalkan
     */
    public static function expireUnpaid(): int
    {
        $window = (int) config('goterapis.payment_window_hours');
        $orders = static::where('status', 'pending_payment')
            ->whereNotNull('accepted_at')
            ->where(fn ($q) => $q
                ->where('accepted_at', '<=', now()->subHours($window))
                ->orWhere('scheduled_at', '<=', now()))
            ->get();

        return $orders->sum(fn (Order $order) => (int) $order->changeStatus(
            'cancelled',
            'Pesanan dibatalkan karena batas pembayaran berakhir.',
            ['cancelled_at' => now(), 'cancel_reason' => 'Pembayaran tidak diselesaikan sampai batas waktu.'],
            ['pending_payment'],
        ));
    }

    public static function completeFinished(): int
    {
        $graceHours = (int) config('goterapis.completion_grace_hours');
        $orders = static::where('status', 'in_progress')->whereNotNull('started_at')->get()
            ->filter(fn (Order $order) => $order->started_at->copy()->addMinutes($order->duration_min)->addHours($graceHours)->isPast());

        return $orders->sum(fn (Order $order) => (int) $order->changeStatus(
            'completed',
            'Pesanan selesai otomatis setelah waktu layanan dan masa tenggang berakhir.',
            ['completed_at' => $order->started_at->copy()->addMinutes($order->duration_min)],
            ['in_progress'],
        ));
    }

    public function changeStatus(string $status, string $message, array $attributes = [], ?array $from = null): bool
    {
        $from ??= [$this->status];
        $changed = DB::transaction(function () use ($status, $attributes, $from) {
            $changed = static::whereKey($this->getKey())
                ->whereIn('status', $from)
                ->update([...$attributes, 'status' => $status, 'updated_at' => now()]);

            if ($changed === 0) {
                return false;
            }

            $this->refresh();
            if ($status === 'completed') {
                $this->earning()->firstOrCreate([], [
                    'therapist_profile_id' => $this->therapist_profile_id,
                    'amount' => $this->payout,
                    'available_at' => $this->completed_at->copy()->addDay(),
                ]);
            }

            return true;
        });

        if (! $changed) {
            return false;
        }

        $this->loadMissing(['user', 'therapistProfile.user']);
        collect([$this->user, $this->therapistProfile?->user])->filter()->unique('id')
            ->reject(fn (User $user) => $user->is(auth()->user()))
            ->each->notify(new OrderStatusChanged($this, $message));

        return true;
    }

    public function hasParticipant(Authenticatable $user): bool
    {
        return $this->user_id === $user->getAuthIdentifier()
            || $this->therapistProfile()->where('user_id', $user->getAuthIdentifier())->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function therapistProfile(): BelongsTo
    {
        return $this->belongsTo(TherapistProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function earning(): HasOne
    {
        return $this->hasOne(Earning::class);
    }
}
