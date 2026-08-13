<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->phone_verified_at === null) {
            return redirect()->route('phone.verify')
                ->with('success', 'Verifikasi nomor WhatsApp-mu dulu supaya terapis bisa menghubungimu.');
        }

        return $next($request);
    }
}
