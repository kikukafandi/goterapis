<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PromotionBanner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $banners = PromotionBanner::visible()->limit(3)->get();

        $products = Product::published()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request): void {
                $term = $request->string('q')->trim()->value();
                $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
            })
            ->when(array_key_exists($request->string('category')->value(), Product::CATEGORIES), fn ($query) => $query->where('category', $request->string('category')->value()))
            ->orderByDesc('is_promoted')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('banners', 'products'));
    }

    public function show(Product $product): View
    {
        abort_unless(Product::published()->whereKey($product)->exists(), 404);

        return view('products.show', compact('product'));
    }
}
