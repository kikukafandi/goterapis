<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_publik_memuat_metadata_lengkap_dan_pencarian_noindex(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('<link rel="canonical" href="'.route('home').'">', false)->assertSee('property="og:title"', false)->assertSee('name="twitter:card"', false)->assertSee('"Organization"', false);
        $this->get(route('cari'))->assertOk()->assertSee('content="noindex, follow"', false)->assertDontSee('lat=');
        $this->get(route('login'))->assertOk()->assertSee('content="noindex, nofollow"', false);
    }

    public function test_landing_lokal_hanya_menampilkan_kombinasi_nyata_dan_resolve_kota_tanpa_peka_kapital(): void
    {
        $service = Service::create(['name' => 'Pijat Tradisional', 'slug' => 'pijat-tradisional', 'category' => 'pijat', 'is_active' => true]);
        $profile = $this->therapist();
        $profile->services()->attach($service, ['price' => 100000, 'duration_min' => 60]);

        $this->get('/terapis/pijat/di/surabaya')->assertOk()->assertSee('Tukang Pijat di Surabaya')->assertSee('Sari')->assertSee('content="index, follow"', false)->assertSee('"ItemList"', false);
        $this->get('/terapis/PIJAT/di/SURABAYA')->assertOk();
        $this->get('/terapis/bekam/di/surabaya')->assertNotFound();
        $profile->update(['is_available' => false]);
        $this->get('/terapis/pijat/di/surabaya')->assertNotFound();
    }

    public function test_sitemap_hanya_memuat_konten_publik_dan_kombinasi_terapis_layak_tersedia(): void
    {
        $service = Service::create(['name' => 'Pijat Tradisional', 'slug' => 'pijat-tradisional', 'category' => 'pijat', 'is_active' => true]);
        $valid = $this->therapist();
        $valid->services()->attach($service, ['price' => 100000, 'duration_min' => 60]);
        $hidden = $this->therapist('Tidak Tersedia', 'Malang', false);
        $hidden->services()->attach($service, ['price' => 100000, 'duration_min' => 60]);
        $published = Article::factory()->create(['published_at' => now()]);
        $draft = Article::factory()->draft()->create();
        $product = Product::factory()->create();
        $draftProduct = Product::factory()->draft()->create();

        $response = $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('seo.local', ['pijat', 'surabaya']))->assertSee(route('terapis.show', $valid))->assertDontSee(route('terapis.show', $hidden))->assertSee(route('artikel.show', $published))->assertDontSee(route('artikel.show', $draft))->assertSee(route('products.show', $product))->assertDontSee(route('products.show', $draftProduct));
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_robots_menunjuk_sitemap_absolut_dan_melarang_area_privat(): void
    {
        $this->get(route('robots'))->assertOk()->assertSee('Sitemap: '.route('sitemap'))->assertSee('Disallow: /admin')->assertSee('Disallow: /mitra');
    }

    private function therapist(string $name = 'Sari', string $city = 'Surabaya', bool $available = true): TherapistProfile
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'therapist']);
        $profile = TherapistProfile::create(['user_id' => $user->id, 'city' => $city, 'verification_status' => 'identitas', 'is_available' => $available]);
        TherapistDocument::create(['therapist_profile_id' => $profile->id, 'type' => 'ktp', 'path' => 'dokumen/ktp.jpg', 'status' => 'approved']);

        return $profile;
    }
}
