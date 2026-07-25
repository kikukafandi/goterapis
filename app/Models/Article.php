<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['published_at' => 'datetime'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /** URL sampul: URL penuh (data demo) atau file lokal (unggahan asli). */
    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        return str_starts_with($this->cover_path, 'http')
            ? $this->cover_path
            : Storage::url($this->cover_path);
    }

    /** Perkiraan waktu baca dalam menit (±200 kata/menit). */
    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags($this->body)) / 200));
    }

    /** Tag konten yang diizinkan dari editor WYSIWYG. */
    private const ALLOWED_TAGS = '<p><br><strong><em><b><i><u><s><mark><h2><h3><h4>'
        .'<ul><ol><li><blockquote><hr><pre><code><figure><figcaption>'
        .'<a><img><span><table><thead><tbody><tr><td><th>';

    /** Atribut khusus per tag (selain `class` yang aman & diizinkan global). */
    private const ALLOWED_ATTRS = [
        'a' => ['href'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    /**
     * Bersihkan HTML editor: batasi ke tag konten & buang atribut berbahaya
     * (onclick/style/dsb.), sisakan hanya `class` + atribut aman per tag, dan
     * pastikan href/src memakai skema aman. Tak ada jalur XSS tersimpan.
     */
    public static function cleanHtml(string $html): string
    {
        $html = trim(strip_tags($html, self::ALLOWED_TAGS));

        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ((new \DOMXPath($doc))->query('//*') as $el) {
            $tag = strtolower($el->nodeName);
            $keep = array_merge(['class'], self::ALLOWED_ATTRS[$tag] ?? []);

            $drop = [];
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $value = trim($attr->nodeValue);

                $safe = in_array($name, $keep, true)
                    && ! ($name === 'href' && ! preg_match('#^(https?:|mailto:|/|\#)#i', $value))
                    && ! ($name === 'src' && ! preg_match('#^(https?:|/)#i', $value));

                if (! $safe) {
                    $drop[] = $attr->nodeName;
                }
            }

            foreach ($drop as $name) {
                $el->removeAttribute($name);
            }

            if ($tag === 'a' && $el->hasAttribute('href')) {
                $el->setAttribute('rel', 'nofollow noopener');
            }
        }

        $container = $doc->getElementsByTagName('div')->item(0);
        $out = '';
        foreach ($container->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }
}
