<?php

namespace App\Http\Controllers;

use App\Models\DeactivationRequest;
use App\Models\Order;
use App\Models\TherapistProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminDeactivationRequestController extends Controller
{
    public function index(): View
    {
        $requests = DeactivationRequest::with(['user', 'reviewer'])->latest()->paginate(20);

        return view('admin.deactivations.index', compact('requests'));
    }

    public function update(Request $request, DeactivationRequest $deactivationRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $deactivationRequest, $data) {
            $deactivationRequest = DeactivationRequest::lockForUpdate()->findOrFail($deactivationRequest->id);
            if ($deactivationRequest->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah ditinjau.']);
            }

            if ($data['status'] === 'approved') {
                $profile = TherapistProfile::where('user_id', $deactivationRequest->user_id)->lockForUpdate()->firstOrFail();
                if (Order::where('therapist_profile_id', $profile->id)->whereIn('status', Order::BLOCKING_STATUSES)->exists()) {
                    throw ValidationException::withMessages(['status' => 'Akun tidak dapat dinonaktifkan karena masih memiliki pesanan aktif.']);
                }
                $deactivationRequest->user()->update(['deactivated_at' => now()]);
                $profile->update(['is_available' => false, 'is_featured' => false]);
            }

            $deactivationRequest->update([...$data, 'pending_key' => null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });

        return back()->with('ok', 'Permintaan penonaktifan telah ditinjau.');
    }
}
