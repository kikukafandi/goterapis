<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Semua gambar unggahan dikonversi ke WebP dan diperkecil sebelum disimpan.
 * Menekan ukuran file di storage VPS lokal.
 */
class ImageService
{
    /**
     * Simpan unggahan sebagai WebP.
     *
     * @param  string  $dir  folder di disk (mis. 'avatars', 'documents/ktp')
     * @param  int  $maxW  lebar maksimum (auto-scale, tak memperbesar)
     * @param  int  $quality  kualitas WebP 0-100
     * @return string path relatif di disk 'public'
     */
    public static function storeWebp(
        UploadedFile $file,
        string $dir = 'uploads',
        int $maxW = 1600,
        int $quality = 72,
    ): string {
        $image = (new ImageManager(new Driver))->decode($file->getRealPath());

        // Perkecil hanya bila lebih lebar dari batas (scaleDown tak memperbesar).
        $image->scaleDown(width: $maxW);

        $path = trim($dir, '/').'/'.Str::ulid().'.webp';
        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: $quality)));

        return $path;
    }

    /** Thumbnail persegi untuk foto profil/kartu. */
    public static function storeSquareWebp(
        UploadedFile $file,
        string $dir = 'avatars',
        int $size = 480,
        int $quality = 72,
    ): string {
        $image = Image::read($file->getRealPath())->coverDown($size, $size);

        $path = trim($dir, '/').'/'.Str::ulid().'.webp';
        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: $quality)));

        return $path;
    }
}
