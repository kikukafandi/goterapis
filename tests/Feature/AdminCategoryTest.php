<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
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

    public function test_kategori_baru_langsung_dipakai_layanan_dan_filter_pencarian(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), ['name' => 'Totok Wajah'])
            ->assertSessionHasNoErrors();

        // Kolom services.category dulu berupa enum; kategori baru mustahil dipakai tanpa migrasi.
        Service::create(['name' => 'Totok Aura', 'slug' => 'totok-aura', 'category' => 'totok-wajah', 'is_active' => true]);

        $this->get(route('cari', ['kategori' => 'totok-wajah']))->assertOk()->assertSee('Totok Wajah');
        $this->get(route('home'))->assertOk()->assertSee('Totok Wajah');
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
        // Middleware admin menolak duluan, jadi tamu pun dipulangkan ke beranda.
        $this->post(route('admin.categories.store'), ['name' => 'Bebas'])->assertRedirect(route('home'));
        $this->assertDatabaseMissing('categories', ['name' => 'Bebas']);
    }
}
