<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_catalog_only_shows_published_products_with_promoted_first(): void
    {
        $latest = Product::factory()->create(['name' => 'Produk Biasa', 'published_at' => now()]);
        $promoted = Product::factory()->promoted()->create(['name' => 'Produk Unggulan', 'published_at' => now()->subDay()]);
        Product::factory()->draft()->create(['name' => 'Produk Rahasia']);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSeeInOrder([$promoted->name, $latest->name])
            ->assertDontSee('Produk Rahasia');
    }

    public function test_catalog_can_search_and_filter_category(): void
    {
        Product::factory()->create(['name' => 'Minyak Jahe Hangat', 'description' => 'Untuk relaksasi', 'category' => 'minyak-terapi']);
        Product::factory()->create(['name' => 'Daun Kering', 'description' => 'Seduhan', 'category' => 'bahan-herbal']);

        $this->get(route('products.index', ['q' => 'relaksasi', 'category' => 'minyak-terapi']))
            ->assertSee('Minyak Jahe Hangat')
            ->assertDontSee('Daun Kering');
    }

    public function test_draft_product_detail_returns_not_found(): void
    {
        $product = Product::factory()->draft()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.products.index'))
            ->assertRedirect(route('home'));
    }

    public function test_admin_can_create_published_promoted_product_with_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Minyak Pijat Jahe',
            'category' => 'minyak-terapi',
            'price' => 75000,
            'stock' => 12,
            'status' => 'published',
            'is_promoted' => '1',
            'image' => UploadedFile::fake()->image('produk.jpg'),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::firstOrFail();
        $this->assertSame('minyak-pijat-jahe', $product->slug);
        $this->assertTrue($product->is_promoted);
        $this->assertNotNull($product->published_at);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_admin_update_replaces_image_and_draft_clears_publication(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('produk/lama.webp', 'lama');
        $product = Product::factory()->create(['image_path' => 'produk/lama.webp']);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => 'Produk Diperbarui',
            'category' => 'produk-herbal',
            'price' => 90000,
            'stock' => 5,
            'status' => 'draft',
            'image' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        Storage::disk('public')->assertMissing('produk/lama.webp');
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertNull($product->published_at);
    }

    public function test_product_image_url_supports_public_and_uploaded_images(): void
    {
        $publicProduct = Product::factory()->make(['image_path' => 'images/produk/jahe.webp']);
        $uploadedProduct = Product::factory()->make(['image_path' => 'produk/jahe.webp']);

        $this->assertSame(asset('images/produk/jahe.webp'), $publicProduct->imageUrl());
        $this->assertSame(Storage::url('produk/jahe.webp'), $uploadedProduct->imageUrl());
    }

    public function test_product_seeder_is_idempotent_and_covers_all_categories(): void
    {
        $this->seed(ProductSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->assertSame(10, Product::count());
        $this->assertEqualsCanonicalizing(array_keys(Product::CATEGORIES), Product::distinct()->pluck('category')->all());
        $this->assertSame(10, Product::published()->count());
        $this->assertSame(3, Product::where('is_promoted', true)->count());
    }

    public function test_admin_can_delete_product_and_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('produk/hapus.webp', 'gambar');
        $product = Product::factory()->create(['image_path' => 'produk/hapus.webp']);

        $this->actingAs($this->admin())->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing('produk/hapus.webp');
    }
}
