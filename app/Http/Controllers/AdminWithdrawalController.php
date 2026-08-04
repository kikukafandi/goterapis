<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminWithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['requested', 'approved', 'rejected'])]])['status'] ?? null;
        $withdrawals = Withdrawal::with('therapistProfile.user')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'requested' THEN 0 ELSE 1 END")
            ->latest()->paginate(20)->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals', 'status'));
    }

    public function approve(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate(['transfer_reference' => ['required', 'string', 'max:255']]);
        $this->transition($withdrawal, ['status' => 'approved', 'transfer_reference' => $data['transfer_reference'], 'processed_at' => now()]);

        return back()->with('success', 'Penarikan disetujui.');
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $this->transition($withdrawal, ['status' => 'rejected', 'rejection_reason' => $data['rejection_reason'], 'processed_at' => now()]);

        return back()->with('success', 'Penarikan ditolak dan saldo dilepas kembali.');
    }

    private function transition(Withdrawal $withdrawal, array $attributes): void
    {
        DB::transaction(function () use ($withdrawal, $attributes) {
            $locked = Withdrawal::lockForUpdate()->findOrFail($withdrawal->id);
            $locked->therapistProfile()->lockForUpdate()->first();
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages(['status' => 'Penarikan sudah diproses.']);
            }
            $locked->update($attributes);
        });
    }
}
