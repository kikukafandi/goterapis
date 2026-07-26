<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TherapistProfile;
use App\Support\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /** Form pemesanan untuk satu terapis. */
    public function create(Request $request, TherapistProfile $therapist)
    {
        abort_if(! $therapist->is_available, 404);
        if ($request->user()->role !== 'user') {
            return redirect()->route('home')->with('error', 'Masuk sebagai pengguna untuk memesan terapis.');
        }

        $therapist->load(['user', 'services']);

        return view('pesanan.create', compact('therapist'));
    }

    /** Simpan pesanan baru (status awal: menunggu konfirmasi terapis). */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'user') {
            return redirect()->route('home')->with('error', 'Masuk sebagai pengguna untuk memesan terapis.');
        }

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

        $therapist = TherapistProfile::where('is_available', true)->findOrFail($data['therapist_profile_id']);

        // Layanan harus benar-benar ditawarkan terapis ini (ambil harga & durasi dari pivot).
        $service = $therapist->services()->where('services.id', $data['service_id'])->first();
        if ($service === null) {
            return back()->withInput()->with('error', 'Layanan tidak tersedia untuk terapis ini.');
        }

        // Model layanan harus diaktifkan terapis.
        $modelUnavailable = ($data['model'] === 'panggilan' && ! $therapist->serves_call)
            || ($data['model'] === 'tempat' && ! $therapist->serves_place);
        if ($modelUnavailable) {
            return back()->withInput()->with('error', 'Model layanan itu tidak tersedia untuk terapis ini.');
        }

        $transportFee = $data['model'] === 'panggilan' ? (int) $therapist->transport_fee : 0;
        $breakdown = Pricing::breakdown((int) $service->pivot->price, $transportFee);

        $order = Order::create([
            'code' => 'GT-'.strtoupper(Str::random(8)),
            'user_id' => $request->user()->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'model' => $data['model'],
            'scheduled_at' => $data['scheduled_at'],
            'duration_min' => (int) $service->pivot->duration_min,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'lat' => $data['model'] === 'panggilan' ? ($data['lat'] ?? null) : null,
            'lng' => $data['model'] === 'panggilan' ? ($data['lng'] ?? null) : null,
            'loc_accuracy' => $data['model'] === 'panggilan' && isset($data['acc']) ? (int) round($data['acc']) : null,
            'start_pin' => (string) random_int(100000, 999999),
            ...$breakdown,
        ]);

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

        return view('pesanan.show', compact('order', 'messages'));
    }

    /** Pelanggan membatalkan pesanan (sebelum layanan berjalan). */
    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        if (! in_array($order->status, ['pending_confirmation', 'pending_payment', 'paid'], true)) {
            return back()->with('error', 'Pesanan tidak bisa dibatalkan pada tahap ini.');
        }

        $data = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:255']]);

        $paid = $order->status === 'paid';
        $r = Pricing::cancellationRefund(
            (int) $order->price, (int) $order->transport_fee, (int) $order->service_fee,
            $paid, $order->scheduled_at, byTherapist: false, now: now(),
        );

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ]);
        if ($paid) {
            $order->payment?->update(['status' => 'refunded']);
        }

        $rupiah = fn (int $n) => 'Rp'.number_format($n, 0, ',', '.');
        $msg = $paid
            ? 'Pesanan dibatalkan. Dana '.$rupiah($r['refund']).' dikembalikan (biaya layanan '.$rupiah($r['fee_kept']).' tidak dikembalikan).'
            : 'Pesanan dibatalkan.';

        return back()->with('success', $msg);
    }

    /** Pelanggan mengonfirmasi layanan selesai. */
    public function complete(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        if ($order->status !== 'in_progress') {
            return back()->with('error', 'Pesanan belum bisa diselesaikan.');
        }

        $order->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Terima kasih! Layanan ditandai selesai.');
    }
}
