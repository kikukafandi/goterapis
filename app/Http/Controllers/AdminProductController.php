<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(): View
    {
        $products = Product::latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['image_path'] = $request->file('image') ? ImageService::storeWebp($request->file('image'), 'produk') : null;
        $this->setPublication($data);
        Product::create($data);

        return redirect()->route('admin.products.index')->with('ok', 'Produk disimpan.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        $this->setPublication($data, $product);

        $oldImage = $product->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageService::storeWebp($request->file('image'), 'produk');
        }

        $product->update($data);

        if (isset($data['image_path']) && $oldImage && ! str_starts_with($oldImage, 'images/')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.index')->with('ok', 'Produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->image_path && Storage::disk('public')->delete($product->image_path);
        $product->delete();

        return redirect()->route('admin.products.index')->with('ok', 'Produk dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'weight_grams' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'origin' => ['nullable', 'string', 'max:255'],
            'storage_instructions' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_promoted' => ['nullable', 'boolean'],
        ]);
        unset($data['image']);
        $data['is_promoted'] = $request->boolean('is_promoted');

        return $data;
    }

    private function setPublication(array &$data, ?Product $product = null): void
    {
        $data['published_at'] = $data['status'] === 'published' ? ($product?->published_at ?? now()) : null;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $suffix = 1;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
