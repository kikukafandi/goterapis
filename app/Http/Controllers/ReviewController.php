<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Pelanggan memberi ulasan untuk pesanan yang selesai. */
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('pesanan.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        if ($order->status !== 'completed') {
            return back()->with('error', 'Ulasan hanya bisa diberikan untuk pesanan yang selesai.');
        }
        if ($order->review()->exists()) {
            return back()->with('error', 'Pesanan ini sudah kamu ulas.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:1000'],
        ]);

        // ponytail: satu rating keseluruhan mengisi kelima dimensi; pecah granular bila diperlukan.
        $order->review()->create([
            'user_id' => $order->user_id,
            'therapist_profile_id' => $order->therapist_profile_id,
            'rating_service' => $data['rating'],
            'rating_punctual' => $data['rating'],
            'rating_manners' => $data['rating'],
            'rating_hygiene' => $data['rating'],
            'rating_accuracy' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);

        $this->recalculateRating($order);

        return back()->with('success', 'Terima kasih atas ulasanmu!');
    }

    /** Segarkan rata-rata rating & jumlah ulasan terapis. */
    private function recalculateRating(Order $order): void
    {
        $profile = $order->therapistProfile;
        $reviews = $profile->reviews()->where('is_hidden', false)->get();

        $profile->update([
            'rating_avg' => round($reviews->avg(fn ($r) => $r->averageRating()) ?? 0, 2),
            'reviews_count' => $reviews->count(),
        ]);
    }
}
