<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TherapistDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->therapistProfile()->with('schedules')->firstOrFail();
        $orders = $profile->orders();
        $earnings = $profile->earnings();

        return view('mitra.dashboard', [
            'profile' => $profile,
            'pendingOrders' => (clone $orders)->where('status', 'pending_confirmation')->with(['user', 'service'])->oldest('scheduled_at')->limit(3)->get(),
            'todaySessions' => (clone $orders)->whereDate('scheduled_at', today())->whereIn('status', ['pending_payment', 'paid', 'accepted', 'therapist_en_route', 'therapist_arrived', 'in_progress'])->with(['user', 'service'])->orderBy('scheduled_at')->get(),
            'todayEarnings' => (clone $earnings)->whereHas('order', fn ($query) => $query->whereDate('completed_at', today()))->sum('amount'),
            'totalEarnings' => (clone $earnings)->sum('amount'),
        ]);
    }

    public function availability(Request $request): RedirectResponse
    {
        $profile = $request->user()->therapistProfile()->firstOrFail();
        $data = $request->validate(['is_available' => ['required', 'boolean']]);
        abort_if($data['is_available'] && ! $profile->isEligible(), 403);
        $profile->update(['is_available' => $data['is_available']]);

        return back()->with('success', $profile->is_available ? 'Kamu sekarang siap menerima pesanan.' : 'Kamu sedang tidak menerima pesanan.');
    }
}
