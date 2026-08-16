<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureGenderIsSet;
use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsureTherapist;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [EnsureUserIsNotBlocked::class]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'therapist' => EnsureTherapist::class,
            'phone' => EnsurePhoneIsVerified::class,
            'gender' => EnsureGenderIsSet::class,
        ]);

        // Webhook Midtrans dipanggil server-ke-server; verifikasi lewat signature, bukan CSRF.
        $middleware->validateCsrfTokens(except: ['midtrans/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
