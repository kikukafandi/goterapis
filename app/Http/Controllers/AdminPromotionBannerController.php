<?php

namespace App\Http\Controllers;

use App\Models\PromotionBanner;
use App\Support\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminPromotionBannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', ['banners' => PromotionBanner::orderBy('sort_order')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new PromotionBanner]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['image_path'] = ImageService::storeWebp($request->file('image'), 'banner-promosi', 1920, 80);
        PromotionBanner::create($data);

        return redirect()->route('admin.banners.index')->with('ok', 'Banner promosi disimpan.');
    }

    public function edit(PromotionBanner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, PromotionBanner $banner): RedirectResponse
    {
        $data = $this->validated($request, false);
        $oldImage = $banner->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageService::storeWebp($request->file('image'), 'banner-promosi', 1920, 80);
        }

        $banner->update($data);

        if (isset($data['image_path'])) {
            $this->deleteUploadedImage($oldImage);
        }

        return redirect()->route('admin.banners.index')->with('ok', 'Banner promosi diperbarui.');
    }

    public function destroy(PromotionBanner $banner): RedirectResponse
    {
        $imagePath = $banner->image_path;
        $banner->delete();
        $this->deleteUploadedImage($imagePath);

        return redirect()->route('admin.banners.index')->with('ok', 'Banner promosi dihapus.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'max:6144', 'dimensions:min_width=1200,min_height=480'],
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:220'],
            'cta_label' => ['nullable', 'string', 'max:40', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'string', 'max:2048', 'required_with:cta_label', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! str_starts_with($value, '/') && ! filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
                    $fail('Tautan CTA harus berupa path internal atau URL HTTPS.');

                    return;
                }

                if (! str_starts_with($value, '/') && parse_url($value, PHP_URL_SCHEME) !== 'https') {
                    $fail('Tautan CTA harus berupa path internal atau URL HTTPS.');
                }
            }],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);
        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function deleteUploadedImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
