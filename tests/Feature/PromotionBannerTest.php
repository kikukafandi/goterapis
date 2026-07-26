<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PromotionBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromotionBannerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_visible_scope_filters_schedule_orders_and_catalog_limits_to_three(): void
    {
        $third = PromotionBanner::factory()->create(['title' => 'Ketiga', 'sort_order' => 3]);
        $first = PromotionBanner::factory()->create(['title' => 'Pertama', 'sort_order' => 1, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $second = PromotionBanner::factory()->create(['title' => 'Kedua', 'sort_order' => 2]);
        PromotionBanner::factory()->create(['title' => 'Keempat', 'sort_order' => 4]);
        PromotionBanner::factory()->create(['is_active' => false]);
        PromotionBanner::factory()->create(['starts_at' => now()->addHour()]);
        PromotionBanner::factory()->create(['ends_at' => now()->subHour()]);

        $this->assertSame([$first->id, $second->id, $third->id], PromotionBanner::visible()->limit(3)->pluck('id')->all());
        $this->get(route('products.index'))->assertViewHas('banners', fn ($banners) => $banners->count() === 3);
    }

    public function test_non_admin_cannot_manage_banners(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get(route('admin.banners.index'))->assertRedirect(route('home'));
    }

    public function test_admin_can_create_banner_with_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post(route('admin.banners.store'), [
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 480),
            'title' => 'Rawat Diri Lebih Baik',
            'sort_order' => 2,
            'is_active' => '1',
        ])->assertRedirect(route('admin.banners.index'));

        $banner = PromotionBanner::firstOrFail();
        $this->assertTrue($banner->is_active);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_banner_validates_cta_url_pair_and_schedule(): void
    {
        Storage::fake('public');
        $base = ['image' => UploadedFile::fake()->image('banner.jpg', 1200, 480), 'title' => 'Kampanye', 'sort_order' => 0];

        $this->actingAs($this->admin())->post(route('admin.banners.store'), $base + ['cta_label' => 'Buka'])->assertSessionHasErrors('cta_url');
        $this->post(route('admin.banners.store'), $base + ['cta_label' => 'Buka', 'cta_url' => 'javascript:alert(1)'])->assertSessionHasErrors('cta_url');
        $this->post(route('admin.banners.store'), $base + ['starts_at' => now()->addDay()->format('Y-m-d H:i:s'), 'ends_at' => now()->format('Y-m-d H:i:s')])->assertSessionHasErrors('ends_at');
    }

    public function test_update_keeps_image_without_upload_and_replaces_after_successful_update(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banner-promosi/lama.webp', 'lama');
        $banner = PromotionBanner::factory()->create(['image_path' => 'banner-promosi/lama.webp']);
        $data = ['title' => 'Judul Baru', 'sort_order' => 0];

        $this->actingAs($this->admin())->put(route('admin.banners.update', $banner), $data)->assertRedirect(route('admin.banners.index'));
        $this->assertSame('banner-promosi/lama.webp', $banner->refresh()->image_path);
        Storage::disk('public')->assertExists('banner-promosi/lama.webp');

        $this->put(route('admin.banners.update', $banner), $data + ['image' => UploadedFile::fake()->image('baru.jpg', 1200, 480)])->assertRedirect(route('admin.banners.index'));
        Storage::disk('public')->assertMissing('banner-promosi/lama.webp');
        Storage::disk('public')->assertExists($banner->refresh()->image_path);
    }

    public function test_delete_removes_record_and_uploaded_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banner-promosi/hapus.webp', 'gambar');
        $banner = PromotionBanner::factory()->create(['image_path' => 'banner-promosi/hapus.webp']);

        $this->actingAs($this->admin())->delete(route('admin.banners.destroy', $banner))->assertRedirect(route('admin.banners.index'));
        $this->assertModelMissing($banner);
        Storage::disk('public')->assertMissing('banner-promosi/hapus.webp');
    }

    public function test_promoted_product_is_not_used_as_banner(): void
    {
        $product = Product::factory()->promoted()->create(['name' => 'Bukan Banner']);

        $this->get(route('products.index'))->assertViewHas('banners', fn ($banners) => $banners->isEmpty())->assertSee($product->name)->assertDontSee('Kampanye GoTerapis');
    }
}
