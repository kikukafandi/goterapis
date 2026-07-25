<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TherapistRegisterController;
use App\Http\Controllers\CariController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TherapistOrderController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Webhook Midtrans (publik, tanpa CSRF — diverifikasi via signature_key)
Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('midtrans.webhook');

// Pencarian & profil terapis (publik)
Route::get('/cari', [CariController::class, 'index'])->name('cari');
Route::get('/terapis/{therapist}', [CariController::class, 'show'])->name('terapis.show');

// Jurnal — artikel publik
Route::get('/artikel', [JurnalController::class, 'index'])->name('artikel.index');
Route::get('/artikel/{article:slug}', [JurnalController::class, 'show'])->name('artikel.show');

// Auth (login bersama, redirect sesuai role)
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::post('/masuk', [LoginController::class, 'login']);

    Route::get('/daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'register']);

    Route::get('/daftar-terapis', [TherapistRegisterController::class, 'show'])->name('register.therapist');
    Route::post('/daftar-terapis', [TherapistRegisterController::class, 'register']);
});
Route::post('/keluar', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Pemesanan (butuh login; guest diarahkan ke /masuk oleh middleware auth)
Route::middleware('auth')->group(function () {
    Route::view('/akun', 'akun')->name('akun');

    Route::get('/terapis/{therapist}/pesan', [OrderController::class, 'create'])->name('pesan.create');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('pesanan.index');
    Route::post('/pesanan', [OrderController::class, 'store'])->name('pesanan.store');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('pesanan.show');
    Route::post('/pesanan/{order}/bayar', [PaymentController::class, 'store'])->name('pesanan.pay');
    Route::patch('/pesanan/{order}/selesai', [OrderController::class, 'complete'])->name('pesanan.complete');
    Route::patch('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('pesanan.cancel');
    Route::post('/pesanan/{order}/ulasan', [ReviewController::class, 'store'])->name('pesanan.review');
});

// Panel terapis (mitra) — kelola pesanan masuk
Route::middleware(['auth', 'therapist'])->prefix('mitra')->name('mitra.')->group(function () {
    Route::get('/pesanan', [TherapistOrderController::class, 'index'])->name('pesanan');
    Route::patch('/pesanan/{order}/terima', [TherapistOrderController::class, 'accept'])->name('pesanan.accept');
    Route::patch('/pesanan/{order}/tolak', [TherapistOrderController::class, 'reject'])->name('pesanan.reject');
    Route::patch('/pesanan/{order}/mulai', [TherapistOrderController::class, 'start'])->name('pesanan.start');
});

// Panel admin (dashboard custom)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/terapis', [AdminController::class, 'therapists'])->name('therapists');
    Route::get('/terapis/{therapist}', [AdminController::class, 'therapist'])->name('therapist');
    Route::patch('/dokumen/{document}', [AdminController::class, 'reviewDocument'])->name('document.review');
    Route::patch('/terapis/{therapist}/status', [AdminController::class, 'updateStatus'])->name('therapist.status');

    Route::post('/artikel/unggah-gambar', [ArticleController::class, 'uploadImage'])->name('articles.upload');
    Route::resource('artikel', ArticleController::class)
        ->parameters(['artikel' => 'article'])
        ->except('show')
        ->names('articles');
});
