<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Setelan situs (hero beranda & SEO) yang diatur admin, disimpan sebagai key => value.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /** Nilai bawaan sekaligus daftar kunci yang boleh diisi admin. */
    public const DEFAULTS = [
        'hero_eyebrow' => 'Terapis panggilan ke rumah',
        'hero_title' => 'Badan pegal? Terapis datang ke rumahmu hari ini.',
        'hero_subtitle' => 'Pilih terapis terverifikasi di kotamu, atur jadwal sendiri, dan bayar setelah pesanan diterima.',
        'hero_image' => null,
        'hero_cta_utama' => 'Cari terapis',
        'hero_cta_mitra' => 'Gabung jadi terapis',
        'hero_cta_panel' => 'Buka panel mitra',
        'seo_title' => 'Temukan Terapis di Sekitarmu',
        'seo_description' => 'Platform komunitas dan pemesanan terapis pijat, bekam, kretek, dan terapi tubuh terpercaya di sekitarmu.',
        'seo_image' => null,
    ];

    /** @return array<string, string|null> */
    public static function map(): array
    {
        return Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
    }

    /** Nilai setelan; jatuh ke bawaan bila kosong. */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::map()[$key] ?? null;

        return filled($value) ? $value : ($default ?? self::DEFAULTS[$key] ?? null);
    }

    /** URL gambar unggahan untuk kunci tertentu, atau $default bila belum diunggah. */
    public static function imageUrl(string $key, string $default): string
    {
        $path = static::get($key);

        return $path ? Storage::url($path) : $default;
    }

    /** @param  array<string, string|null>  $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }
}
