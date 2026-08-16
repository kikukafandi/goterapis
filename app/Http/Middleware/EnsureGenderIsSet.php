<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGenderIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->gender === null) {
            return redirect()->route('phone.verify')
                ->with('success', 'Isi jenis kelaminmu dulu — terapis hanya melayani pelanggan dengan jenis kelamin yang sama.');
        }

        return $next($request);
    }
}
