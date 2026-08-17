<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    /** Lebar maksimum tiap gambar setelan setelah dikonversi ke WebP. */
    private const IMAGE_WIDTHS = ['hero_image' => 1600, 'seo_image' => 1200];

    public function index(): View
    {
        return view('admin.settings.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_eyebrow' => ['nullable', 'string', 'max:60'],
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:240'],
            'hero_image' => ['nullable', 'image', 'max:6144'],
            'hero_cta_utama' => ['required', 'string', 'max:40'],
            'hero_cta_mitra' => ['required', 'string', 'max:40'],
            'hero_cta_panel' => ['required', 'string', 'max:40'],
            'seo_title' => ['required', 'string', 'max:70'],
            'seo_description' => ['required', 'string', 'max:200'],
            'seo_image' => ['nullable', 'image', 'max:6144'],
        ]);

        // Input file kosong tetap terkirim browser; jangan sampai menimpa gambar lama dengan null.
        foreach (self::IMAGE_WIDTHS as $key => $maxWidth) {
            unset($data[$key]);

            if ($file = $request->file($key)) {
                $lama = Setting::get($key);
                $data[$key] = ImageService::storeWebp($file, 'setelan', $maxWidth);
                $lama && Storage::disk('public')->delete($lama);
            }
        }

        Setting::put($data);

        return back()->with('success', 'Setelan situs disimpan.');
    }
}
