<?php

use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminPromotionBannerController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminShopOrderController;
use App\Http\Controllers\AdminTherapistDocumentController;
use App\Http\Controllers\AdminTransactionController;
use App\Http\Controllers\AdminWhatsAppController;
use App\Http\Controllers\AdminWithdrawalController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\GenderController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TherapistRegisterController;
use App\Http\Controllers\CariController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocalSeoController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReportController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopCartController;
use App\Http\Controllers\ShopOrderController;
use App\Http\Controllers\TherapistBalanceController;
use App\Http\Controllers\TherapistDashboardController;
use App\Http\Controllers\TherapistLocationController;
use App\Http\Controllers\TherapistOrderController;
use App\Http\Controllers\TherapistProfileController;
use App\Http\Controllers\TutorialController;
use App\Models\Article;
use App\Models\Category;
use App\Models\TherapistProfile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $therapists = TherapistProfile::query()
        ->eligible()
        ->where('is_available', true)
        ->whereHas('user', fn ($query) => $query->whereNull('blocked_at'))
        ->with(['user', 'services' => fn ($query) => $query->where('is_active', true)])
        ->when(auth()->user()?->gender, fn ($query, $gender) => $query->where('gender', $gender))
        ->orderByDesc('is_featured')
        ->orderByDesc('rating_avg')
        ->limit(6)
        ->get();
    $categories = Category::terurut()
        ->limit(11)
        ->get();
    $hasMoreCategories = $categories->count() > 10;
    $categories = $categories->take($hasMoreCategories ? 9 : 10);
    $cities = TherapistProfile::query()
        ->whereNotNull('city')
        ->where('city', '!=', '')
        ->distinct()
        ->orderBy('city')
        ->pluck('city');
    $articles = Article::query()
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->latest('published_at')
        ->limit(3)
        ->get();

    return view('home', compact('therapists', 'categories', 'hasMoreCategories', 'cities', 'articles'));
})->name('home');
Route::get('/sitemap.xml', [LocalSeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [LocalSeoController::class, 'robots'])->name('robots');
Route::get('/terapis/{kategori}/di/{kotaSlug}', [LocalSeoController::class, 'landing'])->name('seo.local');
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
    Route::post('/masuk', [LoginController::class, 'login'])->middleware('throttle:auth');

    Route::get('/daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'register'])->middleware('throttle:auth');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->middleware('throttle:auth')->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->middleware('throttle:auth')->name('google.callback');

    Route::get('/lupa-sandi', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [PasswordResetController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});
Route::post('/keluar', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Pendaftaran mitra terbuka untuk tamu maupun pelanggan yang ingin naik jadi terapis
// dengan akun yang sama — jadi tak boleh masuk grup `guest`.
Route::get('/daftar-terapis', [TherapistRegisterController::class, 'show'])->name('register.therapist');
Route::post('/daftar-terapis', [TherapistRegisterController::class, 'register'])->middleware('throttle:auth');

// Pemesanan (butuh login; guest diarahkan ke /masuk oleh middleware auth)
Route::middleware('auth')->group(function () {
    Route::view('/akun', 'akun')->name('akun');
    Route::get('/tutorial', TutorialController::class)->name('tutorial');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/verifikasi-nomor', [PhoneVerificationController::class, 'show'])->name('phone.verify');
    Route::post('/jenis-kelamin', GenderController::class)->name('gender.store');
    Route::post('/verifikasi-nomor/kirim', [PhoneVerificationController::class, 'send'])->middleware('throttle:3,1')->name('phone.send');
    Route::post('/verifikasi-nomor', [PhoneVerificationController::class, 'confirm'])->middleware('throttle:10,1')->name('phone.confirm');

    // Nomor WhatsApp wajib terbukti sebelum memesan — terapis menghubungi pelanggan lewat nomor itu.
    // Jenis kelamin wajib terisi karena terapis hanya melayani pelanggan sesama jenis.
    Route::get('/terapis/{therapist}/pesan', [OrderController::class, 'create'])->middleware(['phone', 'gender'])->name('pesan.create');
    Route::get('/terapis/{therapist}/ketersediaan', [OrderController::class, 'availability'])->name('pesan.availability');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('pesanan.index');
    Route::post('/pesanan', [OrderController::class, 'store'])->middleware(['phone', 'gender'])->name('pesanan.store');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('pesanan.show');
    Route::post('/pesanan/{order}/chat', [ChatMessageController::class, 'store'])->name('pesanan.chat.store');
    Route::post('/pesanan/{order}/laporan', [OrderReportController::class, 'store'])->middleware('throttle:5,60')->name('pesanan.reports.store');
    Route::get('/chat', [ChatMessageController::class, 'index'])->name('chat');
    Route::post('/pesanan/{order}/bayar', [PaymentController::class, 'store'])->name('pesanan.pay');
    Route::patch('/pesanan/{order}/selesai', [OrderController::class, 'complete'])->name('pesanan.complete');
    Route::patch('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('pesanan.cancel');
    Route::post('/pesanan/{order}/ulasan', [ReviewController::class, 'store'])->name('pesanan.review');
    Route::get('/keranjang', [ShopCartController::class, 'index'])->name('shop.cart');
    Route::post('/keranjang/{product}', [ShopCartController::class, 'store'])->name('shop.cart.store');
    Route::patch('/keranjang/item/{item}', [ShopCartController::class, 'update'])->name('shop.cart.update');
    Route::delete('/keranjang/item/{item}', [ShopCartController::class, 'destroy'])->name('shop.cart.destroy');
    Route::get('/toko/checkout', [ShopOrderController::class, 'create'])->name('shop.checkout');
    Route::post('/toko/checkout', [ShopOrderController::class, 'store'])->name('shop.orders.store');
    Route::get('/toko/pesanan', [ShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/toko/pesanan/{shopOrder}', [ShopOrderController::class, 'show'])->name('shop.orders.show');
    Route::post('/toko/pesanan/{shopOrder}/bayar', [ShopOrderController::class, 'pay'])->name('shop.orders.pay');
});

// Panel terapis (mitra) — kelola pesanan masuk
Route::middleware(['auth', 'therapist'])->prefix('mitra')->name('mitra.')->group(function () {
    Route::get('/', [TherapistDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/ketersediaan', [TherapistDashboardController::class, 'availability'])->name('availability');
    Route::get('/verifikasi', [TherapistProfileController::class, 'verification'])->name('verifikasi');
    Route::put('/dokumen/{document}', [TherapistProfileController::class, 'replaceDocument'])->name('dokumen.replace');
    Route::get('/profil', [TherapistProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [TherapistProfileController::class, 'update'])->middleware('throttle:10,1')->name('profil.update');
    Route::get('/saldo', [TherapistBalanceController::class, 'index'])->name('saldo');
    Route::post('/penarikan', [TherapistBalanceController::class, 'store'])->middleware('throttle:10,1')->name('withdrawals.store');
    Route::post('/kode-otp', [OtpController::class, 'send'])->middleware('throttle:3,1')->name('otp.send');
    Route::get('/pesanan', [TherapistOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{order}', [TherapistOrderController::class, 'show'])->name('pesanan.show');
    Route::patch('/pesanan/{order}/terima', [TherapistOrderController::class, 'accept'])->name('pesanan.accept');
    Route::patch('/pesanan/{order}/tolak', [TherapistOrderController::class, 'reject'])->name('pesanan.reject');
    Route::patch('/pesanan/{order}/otw', [TherapistOrderController::class, 'enRoute'])->name('pesanan.en-route');
    Route::put('/pesanan/{order}/lokasi', [TherapistLocationController::class, 'update'])->middleware('throttle:therapist-location')->name('pesanan.location');
    Route::patch('/pesanan/{order}/tiba', [TherapistOrderController::class, 'arrive'])->name('pesanan.arrive');
    Route::patch('/pesanan/{order}/mulai', [TherapistOrderController::class, 'start'])->middleware('throttle:start-order')->name('pesanan.start');
});

// Panel admin (dashboard custom)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/whatsapp', AdminWhatsAppController::class)->name('whatsapp');
    Route::get('/transaksi', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transaksi/{payment}', [AdminTransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transaksi/{payment}/refund', [AdminTransactionController::class, 'retryRefund'])->name('transactions.retry-refund');
    Route::get('/pesanan-toko', [AdminShopOrderController::class, 'index'])->name('shop-orders.index');
    Route::get('/pesanan-toko/{shopOrder}', [AdminShopOrderController::class, 'show'])->name('shop-orders.show');
    Route::patch('/pesanan-toko/{shopOrder}/ongkir', [AdminShopOrderController::class, 'shipping'])->name('shop-orders.shipping');
    Route::patch('/pesanan-toko/{shopOrder}/proses', [AdminShopOrderController::class, 'process'])->name('shop-orders.process');
    Route::patch('/pesanan-toko/{shopOrder}/kirim', [AdminShopOrderController::class, 'ship'])->name('shop-orders.ship');
    Route::get('/setelan', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/setelan', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/kategori', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/kategori', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::post('/kategori/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/kategori/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/kategori/{category}/subkategori', [AdminCategoryController::class, 'storeService'])->name('services.store');
    Route::put('/subkategori/{service}', [AdminCategoryController::class, 'updateService'])->name('services.update');
    Route::delete('/subkategori/{service}', [AdminCategoryController::class, 'destroyService'])->name('services.destroy');

    Route::get('/laporan', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::patch('/laporan/{report}', [AdminReportController::class, 'update'])->name('reports.update');
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
