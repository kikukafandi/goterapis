@extends('layouts.admin')
@section('title', 'Artikel')
@section('heading', 'Artikel')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-kabut">Kelola artikel yang ditampilkan kepada pembaca.</p>
    <a href="{{ route('admin.articles.create') }}"
       class="rounded-full bg-daun px-4 py-2 text-sm font-semibold text-white hover:bg-daun-tua">
        Tulis artikel
    </a>
</div>

<div class="overflow-hidden rounded-2xl border border-garis bg-white">
    @forelse ($articles as $article)
        <div class="flex items-center gap-3 border-b border-garis px-4 py-3 last:border-0">
            @if ($article->coverUrl())
                <img src="{{ $article->coverUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-xl object-cover">
            @else
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-daun-muda text-daun">
                    <x-icon name="clipboard" class="h-5 w-5" />
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-arang">{{ $article->title }}</p>
                <p class="truncate text-xs text-kabut">
                    {{ $article->author->name }} ·
                    @if ($article->isPublished())
                        Terbit {{ $article->published_at->translatedFormat('d M Y') }}
                    @else
                        <span class="text-jahe">Draf</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.articles.edit', $article) }}"
               class="rounded-full border border-garis px-3 py-1.5 text-sm font-semibold text-arang hover:bg-kertas">Ubah</a>
            <form method="post" action="{{ route('admin.articles.destroy', $article) }}"
                  onsubmit="return confirm('Hapus artikel ini?')">
                @csrf @method('DELETE')
                <button class="rounded-full border border-garis px-3 py-1.5 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button>
            </form>
        </div>
    @empty
        <p class="px-5 py-10 text-center text-sm text-kabut">Belum ada artikel. Mulai <a href="{{ route('admin.articles.create') }}" class="font-semibold text-daun hover:underline">tulis artikel</a>.</p>
    @endforelse
</div>

<div class="mt-4">{{ $articles->links() }}</div>
@endsection
