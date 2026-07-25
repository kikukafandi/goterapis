<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Support\Payment\MidtransGateway;
use App\Support\Payment\SimulatedGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pakai Midtrans bila dipilih & kredensial terisi; selain itu jatuh ke simulasi (dev/test).
        $this->app->bind(PaymentGateway::class, function () {
            $useMidtrans = config('goterapis.gateway') === 'midtrans'
                && filled(config('services.midtrans.server_key'));

            return $this->app->make($useMidtrans ? MidtransGateway::class : SimulatedGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
