<?php

namespace App\Http\Controllers;

use App\Models\DeactivationRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeactivationRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isTherapist(), 403);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        try {
            DeactivationRequest::create(['user_id' => $request->user()->id, 'reason' => $data['reason'] ?? null]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['reason' => 'Permintaan penonaktifan masih menunggu tinjauan.']);
        }

        return back()->with('ok', 'Permintaan penonaktifan dikirim. Akun tetap aktif hingga disetujui admin.');
    }
}
