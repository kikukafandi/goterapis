<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminPromotionBannerController;
use App\Http\Controllers\AdminTherapistDocumentController;
use App\Http\Controllers\AdminWhatsAppController;
use App\Http\Controllers\AdminWithdrawalController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TherapistRegisterController;
use App\Http\Controllers\CariController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TherapistBalanceController;
use App\Http\Controllers\TherapistLocationController;
use App\Http\Controllers\TherapistOrderController;
use App\Http\Controllers\TherapistProfileController;
use App\Http\Controllers\TutorialController;
use App\Models\TherapistProfile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $therapists = TherapistProfile::query()
        ->where('is_available', true)
        ->whereHas('user', fn ($query) => $query->whereNull('blocked_at'))
        ->with(['user', 'services'])
        ->orderByDesc('is_featured')
        ->orderByDesc('rating_avg')
        ->limit(6)
        ->get();

    return view('home', compact('therapists'));
})->name('home');
Route::get('/legal/{document}', LegalController::class)
    ->whereIn('document', array_keys(config('legal.documents')))
    ->name('legal.show');

// Webhook Midtrans (publik, tanpa CSRF — diverifikasi via signature_key)
Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('midtrans.webhook');

// Pencarian & profil terapis (publik)
Route::get('/cari', [CariController::class, 'index'])->name('cari');
Route::get('/terapis/{therapist}', [CariController::class, 'show'])->name('terapis.show');

// Jurnal — artikel publik
Route::get('/artikel', [JurnalController::class, 'index'])->name('artikel.index');
Route::get('/artikel/{article:slug}', [JurnalController::class, 'show'])->name('artikel.show');
Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('products.show');

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
    Route::get('/tutorial', TutorialController::class)->name('tutorial');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/terapis/{therapist}/pesan', [OrderController::class, 'create'])->name('pesan.create');
    Route::get('/terapis/{therapist}/ketersediaan', [OrderController::class, 'availability'])->name('pesan.availability');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('pesanan.index');
    Route::post('/pesanan', [OrderController::class, 'store'])->name('pesanan.store');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('pesanan.show');
    Route::post('/pesanan/{order}/chat', [ChatMessageController::class, 'store'])->name('pesanan.chat.store');
    Route::get('/chat', [ChatMessageController::class, 'index'])->name('chat');
    Route::post('/pesanan/{order}/bayar', [PaymentController::class, 'store'])->name('pesanan.pay');
    Route::patch('/pesanan/{order}/selesai', [OrderController::class, 'complete'])->name('pesanan.complete');
    Route::patch('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('pesanan.cancel');
    Route::post('/pesanan/{order}/ulasan', [ReviewController::class, 'store'])->name('pesanan.review');
});

// Panel terapis (mitra) — kelola pesanan masuk
Route::middleware(['auth', 'therapist'])->prefix('mitra')->name('mitra.')->group(function () {
    Route::get('/profil', [TherapistProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [TherapistProfileController::class, 'update'])->name('profil.update');
    Route::get('/saldo', [TherapistBalanceController::class, 'index'])->name('saldo');
    Route::post('/penarikan', [TherapistBalanceController::class, 'store'])->name('withdrawals.store');
    Route::get('/pesanan', [TherapistOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{order}', [TherapistOrderController::class, 'show'])->name('pesanan.show');
    Route::patch('/pesanan/{order}/terima', [TherapistOrderController::class, 'accept'])->name('pesanan.accept');
    Route::patch('/pesanan/{order}/tolak', [TherapistOrderController::class, 'reject'])->name('pesanan.reject');
    Route::patch('/pesanan/{order}/otw', [TherapistOrderController::class, 'enRoute'])->name('pesanan.en-route');
    Route::put('/pesanan/{order}/lokasi', [TherapistLocationController::class, 'update'])->middleware('throttle:therapist-location')->name('pesanan.location');
    Route::patch('/pesanan/{order}/tiba', [TherapistOrderController::class, 'arrive'])->name('pesanan.arrive');
    Route::patch('/pesanan/{order}/mulai', [TherapistOrderController::class, 'start'])->name('pesanan.start');
});

// Panel admin (dashboard custom)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/whatsapp', AdminWhatsAppController::class)->name('whatsapp');
    Route::get('/penarikan', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::patch('/penarikan/{withdrawal}/setujui', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::patch('/penarikan/{withdrawal}/tolak', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::get('/terapis', [AdminController::class, 'therapists'])->name('therapists');
    Route::get('/terapis/{therapist}', [AdminController::class, 'therapist'])->name('therapist');
    Route::get('/dokumen/{document}/unduh', AdminTherapistDocumentController::class)->name('document.download');
    Route::patch('/dokumen/{document}', [AdminController::class, 'reviewDocument'])->name('document.review');
    Route::patch('/terapis/{therapist}/status', [AdminController::class, 'updateStatus'])->name('therapist.status');

    Route::post('/artikel/unggah-gambar', [ArticleController::class, 'uploadImage'])->name('articles.upload');
    Route::resource('artikel', ArticleController::class)
        ->parameters(['artikel' => 'article'])
        ->except('show')
        ->names('articles');
    Route::resource('produk', AdminProductController::class)
        ->parameters(['produk' => 'product'])
        ->except('show')
        ->names('products');
    Route::resource('banner-promosi', AdminPromotionBannerController::class)
        ->parameters(['banner-promosi' => 'banner'])
        ->except('show')
        ->names('banners');
});
