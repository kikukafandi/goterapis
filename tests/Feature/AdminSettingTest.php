<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @return array<string, string> */
    private function isian(array $ganti = []): array
    {
        return array_merge([
            'hero_eyebrow' => 'Terapis siaga 24 jam',
            'hero_title' => 'Terapis profesional datang ke rumahmu.',
            'hero_subtitle' => 'Pesan sekarang, terapis berangkat.',
            'hero_cta_utama' => 'Pesan terapis',
            'hero_cta_mitra' => 'Mulai jadi terapis',
            'hero_cta_panel' => 'Masuk panel mitra',
            'seo_title' => 'Pijat Panggilan Terpercaya',
            'seo_description' => 'Pesan terapis pijat panggilan bersertifikat di kotamu, jadwal fleksibel, bayar setelah pesanan diterima.',
        ], $ganti);
    }

    public function test_admin_mengubah_hero_dan_seo_lalu_tampil_di_beranda(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->isian([
                'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 900),
                'seo_image' => UploadedFile::fake()->image('og.jpg', 1200, 630),
            ]))
            ->assertSessionHasNoErrors();

        $heroPath = Setting::get('hero_image');
        Storage::disk('public')->assertExists($heroPath);

        $this->get(route('home'))
            ->assertSee('Terapis siaga 24 jam')
            ->assertSee('Terapis profesional datang ke rumahmu.')
            ->assertSee(Storage::url($heroPath))
            ->assertSee('Pijat Panggilan Terpercaya — GoTerapis')
            ->assertSee('Pesan terapis')
            ->assertSee('Mulai jadi terapis')
            ->assertSee(Storage::url(Setting::get('seo_image')));
    }

    public function test_terapis_melihat_tombol_panel_mitra_di_hero(): void
    {
        Setting::put(['hero_cta_mitra' => 'Mulai jadi terapis', 'hero_cta_panel' => 'Masuk panel mitra']);

        $this->actingAs(User::factory()->create(['role' => 'therapist']))
            ->get(route('home'))
            ->assertSee('Masuk panel mitra')
            ->assertDontSee('Mulai jadi terapis')
            ->assertSee(route('mitra.dashboard'));
    }

    public function test_beranda_memakai_teks_bawaan_saat_setelan_kosong(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(Setting::DEFAULTS['hero_title']);
    }

    public function test_menyimpan_tanpa_unggahan_tidak_menghapus_gambar_lama(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->isian(['hero_image' => UploadedFile::fake()->image('hero.jpg')]));
        $lama = Setting::get('hero_image');

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->isian(['hero_title' => 'Judul baru saja.']))
            ->assertSessionHasNoErrors();

        $this->assertSame($lama, Setting::get('hero_image'));
        Storage::disk('public')->assertExists($lama);
    }

    public function test_judul_wajib_diisi(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->isian(['hero_title' => '', 'seo_title' => '']))
            ->assertSessionHasErrors(['hero_title', 'seo_title']);
    }

    public function test_non_admin_tidak_bisa_mengubah_setelan(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), $this->isian())
            ->assertRedirect();

        $this->assertDatabaseCount('settings', 0);
    }
}
