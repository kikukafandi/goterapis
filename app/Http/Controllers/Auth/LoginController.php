<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt([...$data, 'blocked_at' => null], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return $user->role !== 'admin' && $user->tutorial_seen_at === null
            ? redirect()->route('tutorial')
            : redirect()->intended($this->homeFor($user->role));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => '/admin',
            'therapist' => '/mitra/pesanan',
            default => '/',
        };
    }
}
