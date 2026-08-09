<?php

return [
    // Komisi platform dari harga layanan (bukan dari transport).
    'commission_percent' => env('GOTERAPIS_COMMISSION_PERCENT', 15),

    // Biaya layanan tetap yang dibebankan ke pengguna per pesanan (rupiah).
    'service_fee' => env('GOTERAPIS_SERVICE_FEE', 3000),

    // Batas waktu pengguna membayar setelah pesanan diterima terapis (jam).
    'payment_window_hours' => env('GOTERAPIS_PAYMENT_WINDOW_HOURS', 1),

    // Pembatalan gratis bila > X jam sebelum jadwal.
    'cancel_free_hours' => env('GOTERAPIS_CANCEL_FREE_HOURS', 2),

    // Kompensasi untuk terapis bila pembatalan mendadak (persen dari harga).
    'cancel_compensation_percent' => env('GOTERAPIS_CANCEL_COMPENSATION_PERCENT', 50),

    // Payment gateway aktif.
    'gateway' => env('GOTERAPIS_GATEWAY', 'midtrans'),

    // Radius maksimum (meter) posisi terapis dari titik pelanggan saat memulai layanan panggilan.
    'start_radius_m' => env('GOTERAPIS_START_RADIUS_M', 150),

    // Tenggang penyelesaian otomatis setelah durasi layanan berakhir (jam).
    'completion_grace_hours' => env('GOTERAPIS_COMPLETION_GRACE_HOURS', 2),

    // Batas laporan setelah pesanan selesai (jam).
    'report_window_hours' => env('GOTERAPIS_REPORT_WINDOW_HOURS', 24),
];
