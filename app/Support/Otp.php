<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Kode sekali pakai lewat WhatsApp untuk aksi yang menyangkut uang.
 *
 * Kode selalu dikirim ke nomor pengguna saat ini, bukan nomor yang sedang
 * diisikan di form, supaya penukaran nomor oleh penyusup tetap terhalang.
 */
class Otp
{
    /** ponytail: kode disimpan di cache (TTL bawaan), bukan tabel — tak perlu migrasi maupun pembersihan. */
    private const TTL_SECONDS = 300;

    public function __construct(private WhatsAppGateway $gateway) {}

    /** Terbitkan dan kirim kode enam digit; melempar ValidationException bila gagal. */
    public function send(User $user, string $purpose): void
    {
        $phone = $user->routeNotificationForWhatsApp();

        if ($phone === null) {
            throw ValidationException::withMessages(['code' => 'Nomor WhatsApp di profilmu belum valid.']);
        }
        if (! $this->gateway->enabled()) {
            throw ValidationException::withMessages(['code' => 'Verifikasi WhatsApp sedang tidak tersedia.']);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Kode disimpan sebelum dikirim: gateway WhatsApp kerap membalas lambat atau gagal
        // padahal pesannya sudah sampai. Kalau penyimpanan menunggu balasan, kode yang
        // diterima pengguna tak pernah bisa dipakai. Kode nganggur hangus sendiri dalam 5 menit.
        Cache::put($this->key($user, $purpose), Hash::make($code), self::TTL_SECONDS);

        try {
            $this->gateway->send($phone, "Kode verifikasi GoTerapis: {$code}\nBerlaku 5 menit. Jangan berikan kode ini kepada siapa pun, termasuk yang mengaku admin.");
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['code' => 'Kode gagal dikirim. Cek WhatsApp-mu dulu — kalau kodenya sudah masuk, langsung isikan saja.']);
        }
    }

    /** Kirim tanpa menggagalkan alur pemanggil; dipakai tepat setelah pendaftaran. */
    public function sendQuietly(User $user, string $purpose): void
    {
        try {
            $this->send($user, $purpose);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /** Cocokkan kode lalu hanguskan supaya tidak bisa dipakai dua kali. */
    public function verify(User $user, string $purpose, string $code): bool
    {
        $key = $this->key($user, $purpose);
        $hash = Cache::get($key);

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    /** Kode terikat pada satu pengguna dan satu keperluan agar tidak bisa dipakai silang. */
    private function key(User $user, string $purpose): string
    {
        return "otp:{$purpose}:{$user->id}";
    }
}
