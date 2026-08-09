<?php

use App\Models\Order;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lepaskan slot terapis bila pengguna tak kunjung membayar setelah pesanan diterima.
Schedule::call(fn () => logger()->info('Expiry pembayaran selesai.', ['expired_orders' => Order::expireUnpaid()]))
    ->name('orders:expire-unpaid')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => logger()->info('Penyelesaian otomatis selesai.', ['completed_orders' => Order::completeFinished()]))
    ->name('orders:complete-finished')
    ->everyMinute()
    ->withoutOverlapping();
