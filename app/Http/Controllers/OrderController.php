<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\TherapistProfile;
use App\Notifications\OrderStatusChanged;
use App\Support\Geo;
use App\Support\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /** Form pemesanan untuk satu terapis. */
    public function create(Request $request, TherapistProfile $therapist)
    {
        abort_if(! $therapist->is_available || ! $therapist->isEligible(), 404);
        abort_if($request->user()->role === 'admin', 403);
        abort_if($therapist->user_id === $request->user()->id, 403);

        $therapist->load(['user', 'services' => fn ($query) => $query->availableTo($therapist->gender)]);

        return view('pesanan.create', compact('therapist'));
    }

    public function availability(Request $request, TherapistProfile $therapist)
    {
        abort_if(! $therapist->is_available || ! $therapist->isEligible(), 404);
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'service_id' => ['required', 'integer'],
        ]);
        $service = $therapist->services()->availableTo($therapist->gender)->where('services.id', $data['service_id'])->first();
        if ($service === null) {
            throw ValidationException::withMessages(['service_id' => 'Layanan tidak tersedia untuk terapis ini.']);
        }

        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
        $duration = (int) $service->pivot->duration_min;
        $orders = Order::where('therapist_profile_id', $therapist->id)
            ->whereIn('status', Order::BLOCKING_STATUSES)
            ->whereBetween('scheduled_at', [$date, $date->copy()->endOfDay()])
            ->get(['scheduled_at', 'duration_min']);
        $schedules = $therapist->schedules()->where('day_of_week', $date->dayOfWeek)->get();
        $ranges = $schedules->isEmpty() && ! $therapist->schedule_configured
            ? [['start_time' => '08:00', 'end_time' => '20:00']]
            : $schedules->toArray();
        $slots = collect($ranges)->flatMap(function (array $range) use ($date, $duration, $orders) {
            $slot = $date->copy()->setTimeFromTimeString($range['start_time']);
            $end = $date->copy()->setTimeFromTimeString($range['end_time']);
            $available = [];

            while ($slot->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $slot->copy()->addMinutes($duration);
                $overlaps = $orders->contains(fn (Order $order) => $order->scheduled_at->lt($slotEnd)
                    && $order->scheduled_at->copy()->addMinutes($order->duration_min)->gt($slot));
                if (! $overlaps && $slot->isFuture()) {
                    $available[] = ['value' => $slot->format('Y-m-d\TH:i'), 'label' => $slot->format('H.i')];
                }
                $slot->addMinutes(30);
            }

            return $available;
        })->unique('value')->sortBy('value')->values();

        return response()->json(['timezone' => 'WIB', 'slots' => $slots]);
    }

    /** Simpan pesanan baru (status awal: menunggu konfirmasi terapis). */
    public function store(Request $request)
    {
        abort_if($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'therapist_profile_id' => ['required', 'exists:therapist_profiles,id'],
            'service_id' => ['required', 'exists:services,id'],
            'model' => ['required', 'in:panggilan,tempat'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'address' => ['nullable', 'required_if:model,panggilan', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'acc' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $therapist = TherapistProfile::eligible()
                ->where('is_available', true)
                ->lockForUpdate()
                ->findOrFail($data['therapist_profile_id']);
            abort_if($therapist->user_id === $request->user()->id, 403);

            $service = $therapist->services()->availableTo($therapist->gender)->where('services.id', $data['service_id'])->first();
            if ($service === null) {
                throw ValidationException::withMessages(['service_id' => 'Layanan tidak tersedia untuk terapis ini.']);
            }

            $modelUnavailable = ($data['model'] === 'panggilan' && ! $therapist->serves_call)
                || ($data['model'] === 'tempat' && ! $therapist->serves_place);
            if ($modelUnavailable) {
                throw ValidationException::withMessages(['model' => 'Model layanan itu tidak tersedia untuk terapis ini.']);
            }

            $duration = (int) $service->pivot->duration_min;
            $start = Carbon::parse($data['scheduled_at']);
            $end = $start->copy()->addMinutes($duration);
            $schedules = $therapist->schedules()->where('day_of_week', $start->dayOfWeek)->get();
            if ($therapist->schedule_configured) {
                $insideSchedule = $schedules->contains(fn ($schedule) => $start->format('H:i:s') >= $schedule->start_time
                    && $end->format('H:i:s') <= $schedule->end_time);
                if (! $insideSchedule) {
                    throw ValidationException::withMessages(['scheduled_at' => 'Waktu tersebut berada di luar jadwal layanan terapis.']);
                }
            }

            $overlaps = Order::where('therapist_profile_id', $therapist->id)
                ->whereIn('status', Order::BLOCKING_STATUSES)
                ->where('scheduled_at', '<', $end)
                ->get(['scheduled_at', 'duration_min'])
                ->contains(fn (Order $order) => $order->scheduled_at->copy()->addMinutes($order->duration_min)->gt($start));
            if ($overlaps) {
                throw ValidationException::withMessages(['scheduled_at' => 'Jadwal tersebut sudah terisi. Silakan pilih waktu lain.']);
            }

            $transportFee = $data['model'] === 'panggilan' ? (int) $therapist->transport_fee : 0;
            $breakdown = Pricing::breakdown((int) $service->pivot->price, $transportFee);

            return Order::create([
                'code' => 'GT-'.strtoupper(Str::random(8)),
                'user_id' => $request->user()->id,
                'therapist_profile_id' => $therapist->id,
                'service_id' => $service->id,
                'model' => $data['model'],
                'scheduled_at' => $start,
                'duration_min' => $duration,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'lat' => $data['model'] === 'panggilan' ? ($data['lat'] ?? null) : null,
                'lng' => $data['model'] === 'panggilan' ? ($data['lng'] ?? null) : null,
                'loc_accuracy' => $data['model'] === 'panggilan' && isset($data['acc']) ? (int) round($data['acc']) : null,
                'start_pin' => (string) random_int(100000, 999999),
                ...$breakdown,
            ]);
        });

        $therapist = $order->therapistProfile;

        $therapist->user->notify(new OrderStatusChanged($order, 'Ada pesanan baru yang menunggu konfirmasi.'));

        return redirect()->route('pesanan.show', $order)->with('success', 'Pesanan dikirim. Menunggu terapis menerima — bayar setelah dikonfirmasi.');
    }

    /** Riwayat pesanan milik pengguna. */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['therapistProfile.user', 'service'])
            ->latest()
            ->paginate(10);

        return view('pesanan.index', compact('orders'));
    }

    /** Detail satu pesanan (hanya pemilik). */
    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        $order->messages()->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $order->load(['therapistProfile.user', 'service', 'payment', 'review']);
        $messages = $order->messages()->with('sender')->latest()->limit(50)->get()->reverse()->values();
        $therapistLocation = null;
        if ($order->status === 'therapist_en_route' && $order->model === 'panggilan' && $order->lat !== null && $order->lng !== null) {
            $location = Cache::get(TherapistLocationController::cacheKey($order));
            if ($location !== null) {
                $therapistLocation = [
                    'distance_m' => (int) round(Geo::distanceMeters($location['lat'], $location['lng'], $order->lat, $order->lng)),
                    'accuracy' => $location['accuracy'],
                    'updated_at' => $location['updated_at'],
                ];
            }
        }

        return view('pesanan.show', compact('order', 'messages', 'therapistLocation'));
    }

    /** Pelanggan membatalkan pesanan (sebelum layanan berjalan). */
    public function cancel(Request $request, Order $order, PaymentGateway $gateway)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        $data = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:255']]);

        try {
            $result = DB::transaction(function () use ($order, $gateway, $data) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if (! in_array($order->status, ['pending_confirmation', 'pending_payment', 'paid'], true)) {
                    return null;
                }

                $paid = $order->status === 'paid';
                $refund = Pricing::cancellationRefund(
                    (int) $order->price, (int) $order->transport_fee, (int) $order->service_fee,
                    $paid, $order->scheduled_at, byTherapist: false, now: now(),
                );

                if ($paid) {
                    $gateway->refund($order, $refund['refund']);
                }

                if (! $order->changeStatus('cancelled', 'Pelanggan membatalkan pesanan.', [
                    'cancelled_at' => now(),
                    'cancel_reason' => $data['cancel_reason'] ?? null,
                ], [$order->status])) {
                    return null;
                }

                return compact('paid', 'refund');
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pengembalian dana gagal diproses. Pesanan belum dibatalkan.');
        }

        if ($result === null) {
            return back()->with('error', 'Pesanan tidak bisa dibatalkan pada tahap ini.');
        }

        $rupiah = fn (int $n) => 'Rp'.number_format($n, 0, ',', '.');
        $msg = $result['paid']
            ? 'Pesanan dibatalkan. Dana '.$rupiah($result['refund']['refund']).' dikembalikan (biaya layanan '.$rupiah($result['refund']['fee_kept']).' tidak dikembalikan).'
            : 'Pesanan dibatalkan.';

        return back()->with('success', $msg);
    }

    /** Pelanggan mengonfirmasi layanan selesai. */
    public function complete(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        if (! $order->changeStatus('completed', 'Layanan telah selesai.', ['completed_at' => now()], ['in_progress'])) {
            return back()->with('error', 'Pesanan belum bisa diselesaikan.');
        }

        return back()->with('success', 'Terima kasih! Layanan ditandai selesai.');
    }
}
