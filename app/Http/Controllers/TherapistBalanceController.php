<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Models\TherapistProfile;
use App\Models\Withdrawal;
use App\Support\Otp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TherapistBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->therapistProfile()->firstOrFail();
        $earned = $profile->earnings()->whereHas('order', fn ($query) => $query->where('status', 'completed'));
        $reserved = $profile->withdrawals()->where('status', 'requested')->sum('amount');

        return view('mitra.saldo', [
            'pending' => (clone $earned)->where('available_at', '>', now())->sum('amount'),
            'available' => (clone $earned)->where('available_at', '<=', now())->sum('amount') - $reserved - $profile->withdrawals()->where('status', 'approved')->sum('amount'),
            'withdrawn' => $profile->withdrawals()->where('status', 'approved')->sum('amount'),
            'earnings' => $profile->earnings()->with('order.user')->latest()->limit(10)->get(),
            'withdrawals' => $profile->withdrawals()->latest()->paginate(10),
            'profile' => $profile,
        ]);
    }

    public function store(Request $request, Otp $otp): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:10000'],
            'code' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($request, $data, $otp) {
            $profile = TherapistProfile::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($profile->isEligible(), 403);
            if (blank($profile->bank_name) || blank($profile->bank_account_number) || blank($profile->bank_account_name)) {
                throw ValidationException::withMessages(['amount' => 'Lengkapi rekening bank di profil terlebih dahulu.']);
            }
            $available = Earning::where('therapist_profile_id', $profile->id)
                ->where('available_at', '<=', now())
                ->whereHas('order', fn ($query) => $query->where('status', 'completed'))
                ->sum('amount')
                - Withdrawal::where('therapist_profile_id', $profile->id)->whereIn('status', ['requested', 'approved'])->sum('amount');
            if ($data['amount'] > $available) {
                throw ValidationException::withMessages(['amount' => 'Saldo tersedia tidak mencukupi.']);
            }
            // Diperiksa paling akhir supaya kode tidak hangus gara-gara isian lain yang keliru.
            if (! $otp->verify($request->user(), 'penarikan', $data['code'])) {
                throw ValidationException::withMessages(['code' => 'Kode verifikasi salah atau sudah kedaluwarsa.']);
            }
            // Penarikan yang lolos kode membuktikan nomor WhatsApp itu memang dipegang mitra.
            $request->user()->forceFill(['phone_verified_at' => now()])->save();
            $profile->withdrawals()->create(['amount' => $data['amount'], 'bank_name' => $profile->bank_name, 'bank_account_number' => $profile->bank_account_number, 'bank_account_name' => $profile->bank_account_name]);
        });

        return back()->with('success', 'Permintaan penarikan berhasil dikirim.');
    }
}
