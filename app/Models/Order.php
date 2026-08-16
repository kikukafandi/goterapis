<?php

namespace App\Models;

use App\Notifications\OrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Batalkan pesanan yang tak kunjung dijawab terapis agar pelanggan bisa mencari terapis lain.
     * Belum ada dana masuk pada tahap ini, jadi tidak ada yang perlu dikembalikan.
     *
     * @return int jumlah pesanan yang dibatalkan
     */
    public static function expireUnanswered(): int
    {
        $window = (int) config('goterapis.confirmation_window_hours');
        $orders = static::where('status', 'pending_confirmation')
            ->where(fn ($q) => $q
                ->where('created_at', '<=', now()->subHours($window))
                ->orWhere('scheduled_at', '<=', now()))
            ->get();

        return $orders->sum(fn (Order $order) => (int) $order->changeStatus(
            'cancelled',
            'Pesanan dibatalkan karena terapis tidak menjawab.',
            ['cancelled_at' => now(), 'cancel_reason' => 'Terapis tidak menjawab sampai batas waktu.'],
            ['pending_confirmation'],
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

    /**
     * Pengingat terjadwal: pesanan yang belum dijawab terapis, H-1, dan satu jam sebelum layanan.
     *
     * @return int jumlah pesanan yang diingatkan
     */
    public static function sendReminders(): int
    {
        $unanswered = static::where('status', 'pending_confirmation')
            ->where('created_at', '<=', now()->subMinutes((int) config('goterapis.reminder_unanswered_minutes')))
            ->where('scheduled_at', '>', now())
            ->get()
            ->sum(fn (Order $order) => (int) $order->remind(
                'belum-dijawab',
                'Pesanan belum dijawab. Terima atau tolak agar pelanggan tidak menunggu.',
                therapistOnly: true,
            ));

        $dayBefore = static::where('status', 'paid')
            ->whereBetween('scheduled_at', [now()->addHours(23), now()->addDay()])
            ->get()
            ->sum(fn (Order $order) => (int) $order->remind(
                'h-1',
                'Pengingat: layanan dijadwalkan besok pukul '.$order->scheduled_at->translatedFormat('H:i').'.',
            ));

        $hourBefore = static::where('status', 'paid')
            ->whereBetween('scheduled_at', [now(), now()->addHour()])
            ->get()
            ->sum(fn (Order $order) => (int) $order->remind('h-1jam', 'Pengingat: layanan dimulai satu jam lagi.'));

        return $unanswered + $dayBefore + $hourBefore;
    }

    /**
     * Kirim satu pengingat, sekali saja per pesanan.
     * ponytail: penanda kirim disimpan di cache, pindahkan ke kolom bila riwayat pengingat perlu diaudit.
     */
    private function remind(string $key, string $message, bool $therapistOnly = false): bool
    {
        if (! Cache::add("order-reminder:{$this->id}:{$key}", true, now()->addDays(2))) {
            return false;
        }

        $this->loadMissing(['user', 'therapistProfile.user']);

        collect([$therapistOnly ? null : $this->user, $this->therapistProfile?->user])
            ->filter()->unique('id')
            ->each->notify(new OrderStatusChanged($this, $message));

        return true;
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
