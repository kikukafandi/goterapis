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

// Lepaskan pelanggan bila terapis tak kunjung menjawab pesanan.
Schedule::call(fn () => logger()->info('Pembatalan tanpa jawaban selesai.', ['expired_orders' => Order::expireUnanswered()]))
    ->name('orders:expire-unanswered')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => logger()->info('Penyelesaian otomatis selesai.', ['completed_orders' => Order::completeFinished()]))
    ->name('orders:complete-finished')
    ->everyMinute()
    ->withoutOverlapping();

// Ingatkan pesanan yang belum dijawab serta jadwal H-1 dan satu jam sebelum layanan.
Schedule::call(fn () => logger()->info('Pengingat pesanan selesai.', ['reminded_orders' => Order::sendReminders()]))
    ->name('orders:reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
