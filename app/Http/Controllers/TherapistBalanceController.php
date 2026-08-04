<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Models\TherapistProfile;
use App\Models\Withdrawal;
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
        $earned = $profile->earnings();
        $reserved = $profile->withdrawals()->where('status', 'requested')->sum('amount');

        return view('mitra.saldo', [
            'pending' => (clone $earned)->where('available_at', '>', now())->sum('amount'),
            'available' => (clone $earned)->where('available_at', '<=', now())->sum('amount') - $reserved - $profile->withdrawals()->where('status', 'approved')->sum('amount'),
            'withdrawn' => $profile->withdrawals()->where('status', 'approved')->sum('amount'),
            'withdrawals' => $profile->withdrawals()->latest()->paginate(10),
            'profile' => $profile,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:10000']]);

        DB::transaction(function () use ($request, $data) {
            $profile = TherapistProfile::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            if (blank($profile->bank_name) || blank($profile->bank_account_number) || blank($profile->bank_account_name)) {
                throw ValidationException::withMessages(['amount' => 'Lengkapi rekening bank di profil terlebih dahulu.']);
            }
            $available = Earning::where('therapist_profile_id', $profile->id)->where('available_at', '<=', now())->sum('amount')
                - Withdrawal::where('therapist_profile_id', $profile->id)->whereIn('status', ['requested', 'approved'])->sum('amount');
            if ($data['amount'] > $available) {
                throw ValidationException::withMessages(['amount' => 'Saldo tersedia tidak mencukupi.']);
            }
            $profile->withdrawals()->create([...$data, 'bank_name' => $profile->bank_name, 'bank_account_number' => $profile->bank_account_number, 'bank_account_name' => $profile->bank_account_name]);
        });

        return back()->with('success', 'Permintaan penarikan berhasil dikirim.');
    }
}
