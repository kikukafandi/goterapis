<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** Lempar pengguna ke layar izin Google. */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /** Buat akun pelanggan dari profil Google lalu arahkan ke verifikasi nomor. */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')
                ->withErrors(['email' => 'Masuk lewat Google gagal. Coba lagi atau pakai sandi.']);
        }

        if ($user = User::where('google_id', $googleUser->getId())->first()) {
            if ($user->isBlocked()) {
                return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak aktif atau sedang diblokir.']);
            }
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        // Email pendaftaran biasa tidak pernah diverifikasi, jadi menyambung otomatis ke akun
        // bersandi membuka celah pre-hijacking. Pemilik akun harus membuktikan diri lewat sandi.
        if (User::where('email', $googleUser->getEmail())->exists()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email ini sudah terdaftar. Masuk dengan sandi.']);
        }

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'role' => 'user',
            'legal_version' => config('legal.version'),
            'legal_accepted_at' => now(),
        ]);

        // Google sudah memverifikasi kepemilikan email; kolomnya sengaja di luar mass-assignment.
        $user->forceFill(['email_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('phone.verify');
    }
}
