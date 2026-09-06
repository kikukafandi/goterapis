<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_menambah_kategori_beserta_ikonnya(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name' => 'Refleksi Kaki',
            'position' => 9,
            'icon' => UploadedFile::fake()->image('refleksi.png'),
        ])->assertSessionHasNoErrors();

        $category = Category::where('slug', 'refleksi-kaki')->firstOrFail();
        $this->assertSame('Refleksi Kaki', $category->name);
        $this->assertSame(9, $category->position);
        Storage::disk('public')->assertExists($category->icon_path);
        $this->assertNotNull($category->iconUrl());
    }

    public function test_admin_mengelola_subkategori_secara_dinamis(): void
    {
        $category = Category::where('slug', 'pijat')->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.services.store', $category), [
            'name' => 'Pijat Kepala',
            'allowed_gender' => 'wanita',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $service = Service::where('slug', 'pijat-kepala')->firstOrFail();
        $this->assertSame('pijat', $service->category);
        $this->assertSame('wanita', $service->allowed_gender);
        $this->assertTrue($service->is_active);

        $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => 'Pijat Kepala dan Wajah',
            'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Pijat Kepala dan Wajah', $service->fresh()->name);
        $this->assertFalse($service->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.services.destroy', $service))->assertSessionHasNoErrors();
        $this->assertModelMissing($service);
    }

    public function test_subkategori_baru_tampil_di_pendaftaran_terapis(): void
    {
        $category = Category::where('slug', 'pijat')->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.services.store', $category), [
            'name' => 'Pijat Kepala',
            'is_active' => '1',
        ]);

        $this->post(route('logout'));
        $this->get(route('register.therapist'))->assertOk()->assertSee('Pijat Kepala');
    }

    public function test_subkategori_yang_dipakai_terapis_tidak_bisa_dihapus(): void
    {
        $service = Service::create(['name' => 'Pijat Kepala', 'slug' => 'pijat-kepala', 'category' => 'pijat']);
        $profile = TherapistProfile::create([
            'user_id' => User::factory()->create(['role' => 'therapist'])->id,
            'gender' => 'pria',
            'city' => 'Yogyakarta',
        ]);
        $profile->services()->attach($service, ['price' => 100000, 'duration_min' => 60]);

        $this->actingAs($this->admin())->delete(route('admin.services.destroy', $service))
            ->assertSessionHasErrors('hapus_layanan');

        $this->assertModelExists($service);
    }

    public function test_kategori_baru_langsung_dipakai_layanan_dan_filter_pencarian(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), ['name' => 'Totok Wajah'])
            ->assertSessionHasNoErrors();

        $this->get(route('home'))->assertOk()->assertSee('Totok Wajah');

        Service::create(['name' => 'Totok Aura', 'slug' => 'totok-aura', 'category' => 'totok-wajah', 'is_active' => true]);

        $this->get(route('cari', ['kategori' => 'totok-wajah']))->assertOk()->assertSee('Totok Wajah');
    }

    public function test_beranda_membatasi_kategori_dan_menampilkan_lainnya(): void
    {
        Category::query()->delete();

        foreach (range(1, 11) as $position) {
            $slug = "kategori-{$position}";
            Category::create(['name' => "Kategori {$position}", 'slug' => $slug, 'position' => $position]);
            Service::create(['name' => "Layanan {$position}", 'slug' => "layanan-{$position}", 'category' => $slug, 'is_active' => true]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->pluck('name')->all() === array_map(fn ($position) => "Kategori {$position}", range(1, 9)))
            ->assertViewHas('hasMoreCategories', true)
            ->assertSee('sm:grid-cols-3', false)
            ->assertSee('Lainnya');
    }

    public function test_footer_menampilkan_semua_kategori_sesuai_urutan_admin(): void
    {
        Category::query()->delete();

        foreach (range(1, 6) as $position) {
            Category::create(['name' => "Kategori {$position}", 'slug' => "kategori-{$position}", 'position' => $position]);
        }

        $footer = view('partials.footer')->render();

        $positions = array_map(fn ($position) => strpos($footer, "Kategori {$position}"), range(1, 6));

        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertNotContains(false, $positions);
        $this->assertStringContainsString(route('cari', ['kategori' => 'kategori-1']), $footer);
    }

    public function test_nama_kategori_tidak_boleh_kembar(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), ['name' => 'Pijat'])
            ->assertSessionHasErrors('name');
    }

    public function test_kategori_yang_masih_dipakai_layanan_tidak_bisa_dihapus(): void
    {
        $category = Category::where('slug', 'pijat')->firstOrFail();
        Service::create(['name' => 'Pijat Punggung', 'slug' => 'pijat-punggung', 'category' => 'pijat', 'is_active' => true]);

        $this->actingAs($this->admin())->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('hapus');

        $this->assertModelExists($category);
    }

    public function test_kategori_tanpa_layanan_bisa_dihapus_berikut_ikonnya(): void
    {
        Storage::fake('public');
        $category = Category::create(['slug' => 'sementara', 'name' => 'Sementara', 'icon_path' => 'kategori/x.png']);
        Storage::disk('public')->put('kategori/x.png', 'ikon');

        $this->actingAs($this->admin())->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($category);
        Storage::disk('public')->assertMissing('kategori/x.png');
    }

    public function test_bukan_admin_dilarang_mengelola_kategori(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.categories.index'))->assertRedirect(route('home'));
        $this->post(route('admin.categories.store'), ['name' => 'Bebas'])->assertRedirect(route('home'));
        $this->assertDatabaseMissing('categories', ['name' => 'Bebas']);
    }
}
