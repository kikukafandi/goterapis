<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class JurnalController extends Controller
{
    public function index(): View
    {
        $articles = Article::with('author')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(9);

        return view('artikel.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $article->load('author');

        $more = Article::with('author')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereKeyNot($article->id)
            ->latest('published_at')
            ->take(2)
            ->get();

        return view('artikel.show', compact('article', 'more'));
    }
}
