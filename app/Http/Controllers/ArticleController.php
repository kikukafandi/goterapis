<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::with('author')->latest()->paginate(15);

        return view('admin.articles.index', compact('articles'));
    }

    public function create(): View
    {
        return view('admin.articles.form', ['article' => new Article]);
    }

    /** Unggah gambar dari editor → simpan ke storage, balas format yang dipahami CKEditor. */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('upload')->store('artikel/konten', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['cover_path'] = $request->file('cover')?->store('artikel', 'public');

        Article::create($data);

        return redirect()->route('admin.articles.index')->with('ok', 'Artikel disimpan.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title'], $article->id);

        if ($request->hasFile('cover')) {
            Storage::disk('public')->delete($article->cover_path ?? '');
            $data['cover_path'] = $request->file('cover')->store('artikel', 'public');
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('ok', 'Artikel diperbarui.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        Storage::disk('public')->delete($article->cover_path ?? '');
        $article->delete();

        return redirect()->route('admin.articles.index')->with('ok', 'Artikel dihapus.');
    }

    /**
     * @return array{title: string, meta_title: ?string, excerpt: ?string, body: string, published_at: ?string}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'published_at' => ['nullable', 'date'],
        ]);

        unset($validated['cover']); // disimpan terpisah sebagai cover_path
        $validated['body'] = Article::cleanHtml($validated['body']); // WYSIWYG → HTML aman

        return $validated;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (Article::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }
}
