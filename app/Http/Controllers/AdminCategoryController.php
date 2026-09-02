<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::terurut()->with('services')->withCount('services')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Category::create([
            'slug' => Str::slug($data['name']),
            'name' => $data['name'],
            'position' => $data['position'],
            'icon_path' => $request->file('icon')?->store('kategori', 'public'),
        ]);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);

        // Slug dibiarkan tetap: kolom services.category menunjuk ke sana, dan mengubahnya
        // akan memutus layanan yang sudah terlanjur memakai kategori ini.
        $category->fill(['name' => $data['name'], 'position' => $data['position']]);

        if ($icon = $request->file('icon')) {
            $lama = $category->icon_path;
            $category->icon_path = $icon->store('kategori', 'public');
            $lama && Storage::disk('public')->delete($lama);
        }

        $category->save();

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function storeService(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedService($request);

        Service::create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'category' => $category->slug,
        ]);

        return back()->with('success', 'Subkategori ditambahkan.');
    }

    public function updateService(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validatedService($request, $service));

        return back()->with('success', 'Subkategori diperbarui.');
    }

    public function destroyService(Service $service): RedirectResponse
    {
        if ($service->therapists()->exists()) {
            throw ValidationException::withMessages([
                'hapus_layanan' => "Subkategori \"{$service->name}\" masih dipakai terapis.",
            ]);
        }

        $service->delete();

        return back()->with('success', 'Subkategori dihapus.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if (Service::where('category', $category->slug)->exists()) {
            throw ValidationException::withMessages([
                'hapus' => "Kategori \"{$category->name}\" masih dipakai layanan. Pindahkan layanannya dulu.",
            ]);
        }

        $category->icon_path && Storage::disk('public')->delete($category->icon_path);
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    /** @return array{name: string, allowed_gender: ?string, is_active: bool} */
    private function validatedService(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('services')->ignore($service)],
            'allowed_gender' => ['nullable', Rule::in(['pria', 'wanita'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'allowed_gender' => $data['allowed_gender'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** @return array{name: string, position: int} */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories')->ignore($category)],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'icon' => ['nullable', 'image', 'max:1024'],
        ]);

        return ['name' => $data['name'], 'position' => (int) ($data['position'] ?? 0)];
    }
}
