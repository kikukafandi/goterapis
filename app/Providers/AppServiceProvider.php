<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Support\Payment\MidtransGateway;
use App\Support\Payment\SimulatedGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            $useMidtrans = config('goterapis.gateway') === 'midtrans'
                && filled(config('services.midtrans.server_key'));

            if ($this->app->environment('production') && ! $useMidtrans) {
                throw new \RuntimeException('Midtrans wajib dikonfigurasi untuk pembayaran produksi.');
            }

            return $this->app->make($useMidtrans ? MidtransGateway::class : SimulatedGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            foreach (['operator_name', 'operator_address', 'operator_email', 'version', 'effective_date'] as $key) {
                $value = (string) config("legal.{$key}");

                if (blank($value) || str_contains(strtoupper($value), 'DRAFT')) {
                    throw new \RuntimeException("Konfigurasi legal.{$key} wajib diisi sebelum produksi.");
                }
            }
        }

        RateLimiter::for('therapist-location', fn (Request $request) => Limit::perMinute(12)
            ->by($request->user()->id.'.'.$request->route('order')));
    }
}
