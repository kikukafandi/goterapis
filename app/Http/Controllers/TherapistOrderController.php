<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Geo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TherapistOrderController extends Controller
{
    /** Status pesanan yang masih bisa diterima/ditolak terapis (sebelum pengguna membayar). */
    private const ACTIONABLE = ['pending_confirmation'];

    /** Pesanan masuk untuk terapis yang login. */
    public function index(Request $request)
    {
        $profile = $request->user()->therapistProfile;
        if ($profile === null) {
            return redirect()->route('akun')->with('error', 'Lengkapi profil terapis dulu untuk menerima pesanan.');
        }

        $orders = Order::where('therapist_profile_id', $profile->id)
            ->with(['user', 'service'])
            ->latest()
            ->paginate(10);

        return view('mitra.pesanan', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        if (! $order->hasParticipant($request->user()) || $order->user_id === $request->user()->id) {
            return redirect()->route('mitra.pesanan')->with('error', 'Pesanan tidak ditemukan.');
        }

        $order->messages()->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $order->load(['user', 'service']);
        $messages = $order->messages()->with('sender')->latest()->limit(50)->get()->reverse()->values();

        return view('mitra.pesanan-show', compact('order', 'messages'));
    }

    /** Terima pesanan. */
    public function accept(Request $request, Order $order)
    {
        if ($stop = $this->guard($request, $order)) {
            return $stop;
        }

        $order->changeStatus('pending_payment', 'Terapis menerima pesanan.', ['accepted_at' => now()]);

        return back()->with('success', "Pesanan {$order->code} diterima. Menunggu pembayaran pelanggan.");
    }

    /** Tolak pesanan. */
    public function reject(Request $request, Order $order)
    {
        if ($stop = $this->guard($request, $order)) {
            return $stop;
        }

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order->changeStatus('rejected', 'Terapis menolak pesanan.', [
            'cancelled_at' => now(),
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ]);

        return back()->with('success', "Pesanan {$order->code} ditolak.");
    }

    public function enRoute(Request $request, Order $order)
    {
        if ($stop = $this->guardOwner($request, $order)) {
            return $stop;
        }
        if ($order->status !== 'paid' || $order->model !== 'panggilan') {
            return back()->with('error', 'Status OTW hanya tersedia untuk layanan panggilan yang sudah dibayar.');
        }

        $order->changeStatus('therapist_en_route', 'Terapis sedang menuju lokasi pelanggan.');

        return back()->with('success', "Status pesanan {$order->code} diubah menjadi OTW.");
    }

    public function arrive(Request $request, Order $order)
    {
        if ($stop = $this->guardOwner($request, $order)) {
            return $stop;
        }
        if ($order->status !== 'therapist_en_route' || $order->model !== 'panggilan') {
            return back()->with('error', 'Status tiba hanya tersedia setelah terapis OTW.');
        }

        $order->changeStatus('therapist_arrived', 'Terapis sudah tiba di lokasi pelanggan.');
        Cache::forget(TherapistLocationController::cacheKey($order));

        return back()->with('success', "Terapis sudah tiba untuk pesanan {$order->code}.");
    }

    /** Mulai layanan dengan PIN dari pelanggan. */
    public function start(Request $request, Order $order)
    {
        if ($stop = $this->guardOwner($request, $order)) {
            return $stop;
        }
        $requiredStatus = $order->model === 'panggilan' ? 'therapist_arrived' : 'paid';
        if ($order->status !== $requiredStatus) {
            return back()->with('error', 'Pesanan belum bisa dimulai.');
        }

        $data = $request->validate([
            'pin' => ['required', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'acc' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Layanan panggilan dengan titik pelanggan: terapis wajib berada di lokasi (anti-fake).
        if ($order->model === 'panggilan' && $order->lat !== null && $order->lng !== null) {
            if (! isset($data['lat'], $data['lng'])) {
                return back()->with('error', 'Aktifkan lokasi untuk memulai layanan panggilan.');
            }
            $radius = (int) config('goterapis.start_radius_m');
            $distance = Geo::distanceMeters((float) $data['lat'], (float) $data['lng'], (float) $order->lat, (float) $order->lng);

            // Longgarkan sebesar ketidakpastian kedua pembacaan (GPS bisa meleset, terutama non-mobile).
            // Tolak hanya bila yakin jauh: jarak terdekat yang mungkin masih di luar radius.
            $slack = (int) $order->loc_accuracy + (int) round($data['acc'] ?? 0);
            if ($distance - $slack > $radius) {
                return back()->with('error', 'Kamu sekitar '.round($distance).' m dari titik pelanggan (maks '.$radius.' m). Dekati lokasi lalu coba lagi.');
            }
        }

        if (! hash_equals($order->start_pin, $data['pin'])) {
            return back()->with('error', 'PIN salah. Minta PIN yang benar ke pelanggan.');
        }

        $order->changeStatus('in_progress', 'Layanan telah dimulai.', ['started_at' => now()]);

        return back()->with('success', "Layanan {$order->code} dimulai.");
    }

    /**
     * Pesanan harus milik profil terapis ini dan masih bisa ditindak.
     * Kembalikan RedirectResponse bila tidak boleh, atau null bila lolos.
     */
    private function guard(Request $request, Order $order): ?RedirectResponse
    {
        if ($stop = $this->guardOwner($request, $order)) {
            return $stop;
        }
        if (! in_array($order->status, self::ACTIONABLE, true)) {
            return back()->with('error', 'Pesanan sudah ditindak sebelumnya.');
        }

        return null;
    }

    private function guardOwner(Request $request, Order $order): ?RedirectResponse
    {
        $profile = $request->user()->therapistProfile;

        if ($profile === null || $order->therapist_profile_id !== $profile->id) {
            return redirect()->route('mitra.pesanan')->with('error', 'Pesanan tidak ditemukan.');
        }

        return null;
    }
}
