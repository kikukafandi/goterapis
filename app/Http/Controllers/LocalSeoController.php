<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapistProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LocalSeoController extends Controller
{
    public function landing(string $kategori, string $kotaSlug)
    {
        $category = Service::query()->where('is_active', true)->whereRaw('LOWER(category) = ?', [Str::lower($kategori)])->value('category');
        abort_unless($category, 404);

        $cities = $this->eligible()->whereHas('services', fn (Builder $query) => $query->where('category', $category)->where('is_active', true))->distinct()->pluck('city');
        $city = $cities->first(fn (string $city) => Str::slug($city) === Str::lower($kotaSlug));
        abort_unless($city, 404);

        $therapists = $this->eligible()->whereRaw('LOWER(city) = ?', [Str::lower($city)])
            ->whereHas('services', fn (Builder $query) => $query->where('category', $category)->where('is_active', true))
            ->with(['user', 'services' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('is_featured')->orderByDesc('rating_avg')->paginate(9);
        abort_if($therapists->isEmpty(), 404);

        $related = $this->combinations()->reject(fn (array $item) => $item['category'] === $category && $item['city'] === $city)->take(8);

        return view('seo.local', compact('category', 'city', 'therapists', 'related'));
    }

    public function sitemap(): Response
    {
        $urls = collect([['loc' => route('home')], ['loc' => route('artikel.index')], ['loc' => route('products.index')]])
            ->concat(collect(array_keys(config('legal.documents')))->map(fn ($document) => ['loc' => route('legal.show', $document)]))
            ->concat(Article::query()->whereNotNull('published_at')->where('published_at', '<=', now())->get()->map(fn ($article) => ['loc' => route('artikel.show', $article), 'lastmod' => $article->updated_at?->toAtomString()]))
            ->concat(Product::published()->get()->map(fn ($product) => ['loc' => route('products.show', $product), 'lastmod' => $product->updated_at?->toAtomString()]))
            ->concat($this->eligible()->get()->map(fn ($profile) => ['loc' => route('terapis.show', $profile), 'lastmod' => $profile->updated_at?->toAtomString()]))
            ->concat($this->combinations()->map(fn (array $item) => ['loc' => route('seo.local', [$item['category'], Str::slug($item['city'])])]))
            ->unique('loc');

        return response()->view('seo.sitemap', compact('urls'))->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /mitra\nDisallow: /akun\nDisallow: /pesanan\nDisallow: /chat\nDisallow: /notifikasi\nDisallow: /masuk\nDisallow: /daftar\nSitemap: ".route('sitemap')."\n")->header('Content-Type', 'text/plain');
    }

    private function eligible(): Builder
    {
        return TherapistProfile::query()->eligible()->where('is_available', true)->whereNotNull('city')->where('city', '!=', '')->whereHas('user', fn ($query) => $query->whereNull('blocked_at'));
    }

    private function combinations()
    {
        return $this->eligible()->with(['services' => fn ($query) => $query->where('is_active', true)])->get()
            ->flatMap(fn ($profile) => $profile->services->map(fn ($service) => ['category' => $service->category, 'city' => $profile->city]))
            ->unique(fn (array $item) => $item['category'].'|'.Str::lower($item['city']))->values();
    }
}
