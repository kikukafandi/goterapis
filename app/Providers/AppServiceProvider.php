<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\Category;
use App\Support\Payment\MidtransGateway;
use App\Support\Payment\SimulatedGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        View::composer('partials.footer', fn ($view) => $view->with(
            'footerCategories',
            Category::terurut()->pluck('name', 'slug'),
        ));

        if ($this->app->environment('production')) {
            foreach (['operator_name', 'operator_address', 'operator_email', 'version', 'effective_date'] as $key) {
                $value = (string) config("legal.{$key}");

                if (blank($value) || str_contains(strtoupper($value), 'DRAFT')) {
                    throw new \RuntimeException("Konfigurasi legal.{$key} wajib diisi sebelum produksi.");
                }
            }
        }

        // Rem brute force: ketat per kombinasi email+IP, longgar per IP untuk password spraying.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(str((string) $request->input('email'))->lower()->toString().'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('therapist-location', fn (Request $request) => Limit::perMinute(12)
            ->by($request->user()->id.'.'.$request->route('order')));
        RateLimiter::for('start-order', fn (Request $request) => Limit::perMinute(5)
            ->by($request->user()->id.'.'.$request->route('order')));
    }
}
