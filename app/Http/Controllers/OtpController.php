<?php

namespace App\Http\Controllers;

use App\Support\Otp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtpController extends Controller
{
    /** Kirim kode verifikasi ke nomor WhatsApp pengguna saat ini. */
    public function send(Request $request, Otp $otp): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', Rule::in(['penarikan', 'nomor'])],
        ]);

        $otp->send($request->user(), $data['purpose']);

        return back()->with('success', 'Kode verifikasi dikirim ke WhatsApp-mu. Berlaku 5 menit.');
    }
}
