<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTherapist
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'therapist') {
            return redirect()->route('home')->with('error', 'Halaman ini khusus untuk terapis.');
        }

        return $next($request);
    }
}
