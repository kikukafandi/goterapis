<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Otp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        return $request->user()->phone_verified_at === null
            ? view('auth.verifikasi-nomor')
            : redirect()->to($this->tujuan($request));
    }

    /** Simpan nomor (bila diperbaiki) lalu kirim kode ke nomor itu. */
    public function send(Request $request, Otp $otp): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $user = $request->user();
        $user->update(['phone' => $data['phone']]);
        $otp->send($user, 'daftar');

        return back()->with('success', 'Kode verifikasi dikirim ke WhatsApp-mu. Berlaku 5 menit.');
    }

    public function confirm(Request $request, Otp $otp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $otp->verify($user, 'daftar', $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Kode verifikasi salah atau sudah kedaluwarsa.']);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return redirect()->to($this->tujuan($request))->with('success', 'Nomor WhatsApp-mu sudah terverifikasi.');
    }

    /** Terapis lanjut ke berkas verifikasi, pelanggan ke tutorial. */
    private function tujuan(Request $request): string
    {
        return $request->user()->isTherapist() ? route('mitra.verifikasi') : route('tutorial');
    }
}
