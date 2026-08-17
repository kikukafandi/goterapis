<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_non_admin_cannot_access_articles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/admin/artikel')
            ->assertRedirect(route('home'));
    }

    public function test_editor_page_renders_for_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin/artikel/create')
            ->assertOk()
            ->assertSee('Judul SEO')
            ->assertSee('artikelEditor', false);
    }

    public function test_admin_can_create_article_with_cover_and_seo_title(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/artikel', [
            'title' => 'Manfaat Bekam untuk Kebugaran',
            'meta_title' => 'Manfaat Bekam — Panduan Ringkas',
            'excerpt' => 'Ringkasan singkat.',
            'body' => 'Isi artikel yang cukup panjang untuk dibaca.',
            'cover' => UploadedFile::fake()->image('sampul.jpg'),
            'published_at' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('admin.articles.index'));

        $article = Article::first();
        $this->assertSame('manfaat-bekam-untuk-kebugaran', $article->slug);
        $this->assertSame('Manfaat Bekam — Panduan Ringkas', $article->meta_title);
        $this->assertTrue($article->isPublished());
        Storage::disk('public')->assertExists($article->cover_path);
    }

    public function test_slug_stays_unique_across_articles(): void
    {
        $admin = $this->admin();
        Article::factory()->create(['slug' => 'judul-sama', 'title' => 'Judul Sama']);

        $this->actingAs($admin)->post('/admin/artikel', [
            'title' => 'Judul Sama',
            'body' => 'Isi berbeda.',
        ])->assertRedirect();

        $this->assertSame('judul-sama-2', Article::latest('id')->first()->slug);
    }

    public function test_admin_can_update_article(): void
    {
        $article = Article::factory()->draft()->create();

        $this->actingAs($this->admin())->put("/admin/artikel/{$article->id}", [
            'title' => 'Judul Diperbarui',
            'body' => 'Isi diperbarui.',
        ])->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Judul Diperbarui',
            'slug' => 'judul-diperbarui',
        ]);
    }

    // Dengan disk 'throw' => true, delete('') menunjuk ke root disk dan melempar
    // UnableToDeleteFile. Artikel tanpa sampul harus tetap bisa disunting & dihapus.
    public function test_artikel_tanpa_sampul_tetap_bisa_diganti_sampul_dan_dihapus(): void
    {
        Storage::fake('public');
        $article = Article::factory()->draft()->create(['cover_path' => null]);

        $this->actingAs($this->admin())->put("/admin/artikel/{$article->id}", [
            'title' => 'Artikel Bersampul Baru',
            'body' => 'Isi.',
            'cover' => UploadedFile::fake()->image('sampul.jpg'),
        ])->assertRedirect(route('admin.articles.index'))->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists($article->fresh()->cover_path);

        $polos = Article::factory()->draft()->create(['cover_path' => null]);
        $this->actingAs($this->admin())->delete("/admin/artikel/{$polos->id}")
            ->assertRedirect(route('admin.articles.index'));
        $this->assertModelMissing($polos);
    }
}
