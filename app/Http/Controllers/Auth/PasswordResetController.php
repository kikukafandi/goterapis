<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /** Form permintaan tautan atur ulang. */
    public function request()
    {
        return view('auth.lupa-sandi');
    }

    /** Kirim tautan atur ulang ke email pengguna. */
    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($data);

        // Balasan selalu sama agar email yang terdaftar tidak bisa ditebak dari halaman ini.
        return back()->with('status', trans(Password::RESET_LINK_SENT));
    }

    /** Form isian kata sandi baru dari tautan email. */
    public function reset(Request $request, string $token)
    {
        return view('auth.atur-ulang-sandi', [
            'token' => $token,
            'email' => (string) $request->query('email'),
        ]);
    }

    /** Simpan kata sandi baru dan hanguskan token. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($data, function ($user, string $password) {
            // Cast 'hashed' pada model User yang melakukan hashing.
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => trans($status)]);
        }

        return redirect()->route('login')->with('status', trans($status));
    }
}
