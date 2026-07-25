<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurnalTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_published_and_hides_drafts(): void
    {
        $published = Article::factory()->create(['title' => 'Terbit Nih']);
        $draft = Article::factory()->draft()->create(['title' => 'Masih Draf']);

        $this->get('/artikel')
            ->assertOk()
            ->assertSee('Terbit Nih')
            ->assertDontSee('Masih Draf');
    }

    public function test_published_article_is_readable(): void
    {
        $article = Article::factory()->create();

        $this->get("/artikel/{$article->slug}")
            ->assertOk()
            ->assertSee($article->title);
    }

    public function test_draft_article_returns_404(): void
    {
        $draft = Article::factory()->draft()->create();

        $this->get("/artikel/{$draft->slug}")->assertNotFound();
    }

    public function test_future_dated_article_returns_404(): void
    {
        $scheduled = Article::factory()->create(['published_at' => now()->addWeek()]);

        $this->get("/artikel/{$scheduled->slug}")->assertNotFound();
    }

    public function test_clean_html_keeps_editorial_tags_and_strips_danger(): void
    {
        $dirty = '<h2 onclick="x()">Judul</h2><p>Aman <strong>tebal</strong></p>'
            .'<script>alert(1)</script><p style="color:red">warna</p>'
            .'<a href="javascript:alert(1)">jahat</a><a href="https://ok.test">baik</a>'
            .'<img src="/storage/artikel/konten/a.png" alt="ok" onerror="x()">'
            .'<img src="javascript:alert(1)">'
            .'<table><tr><td>sel</td></tr></table>';

        $clean = Article::cleanHtml($dirty);

        $this->assertStringContainsString('<h2>Judul</h2>', $clean);
        $this->assertStringContainsString('<strong>tebal</strong>', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('href="https://ok.test"', $clean);
        $this->assertStringContainsString('src="/storage/artikel/konten/a.png"', $clean);
        $this->assertStringContainsString('<td>sel</td>', $clean);
    }

    public function test_reading_minutes_is_at_least_one(): void
    {
        $article = Article::factory()->make(['body' => 'Tiga kata saja.']);

        $this->assertSame(1, $article->readingMinutes());
    }
}
