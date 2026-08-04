@extends('layouts.admin')
@section('title', 'Artikel')
@section('heading', 'Artikel')
@section('content')
<header class="mb-6 flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div><h2 class="font-display text-2xl font-bold text-arang">Artikel</h2><p class="mt-1.5 text-sm leading-6 text-kabut">Kelola konten edukasi dan informasi yang dibaca pengguna GoTerapis.</p></div>
    <a href="{{ route('admin.articles.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-daun px-5 text-sm font-semibold text-white hover:bg-daun-tua">Tulis artikel</a>
</header>
<div class="overflow-hidden rounded-card border border-garis bg-white">
    @forelse ($articles as $article)
        <article class="flex flex-col gap-3 border-b border-garis px-4 py-4 last:border-0 sm:flex-row sm:items-center sm:px-5">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                @if ($article->coverUrl())<img src="{{ $article->coverUrl() }}" alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded-xl object-cover">@else<span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-daun-muda text-daun"><x-icon name="clipboard" class="h-5 w-5" /></span>@endif
                <div class="min-w-0 flex-1"><p class="truncate font-semibold text-arang">{{ $article->title }}</p><div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-kabut"><span>{{ $article->author->name }}</span>@if ($article->isPublished())<span class="rounded-full bg-daun-muda px-2 py-1 font-semibold text-daun-tua">Terbit {{ $article->published_at->translatedFormat('d M Y') }}</span>@else<span class="rounded-full bg-kunyit-muda px-2 py-1 font-semibold text-arang">Draf</span>@endif</div></div>
            </div>
            <div class="flex w-full gap-2 sm:w-auto"><a href="{{ route('admin.articles.edit', $article) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-full border border-garis px-4 text-sm font-semibold text-arang hover:bg-kertas sm:flex-none">Ubah</a><form method="post" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?')" class="flex-1 sm:flex-none">@csrf @method('DELETE')<button class="min-h-10 w-full rounded-full border border-garis px-4 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button></form></div>
        </article>
    @empty
        <div class="px-6 py-14 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="clipboard" class="h-5 w-5" /></span><h3 class="mt-4 font-display text-lg font-bold text-arang">Belum ada artikel</h3><p class="mt-1 text-sm text-kabut">Mulai terbitkan konten edukasi untuk pengguna.</p><a href="{{ route('admin.articles.create') }}" class="mt-5 inline-flex min-h-11 items-center rounded-full bg-daun px-5 text-sm font-semibold text-white">Tulis artikel</a></div>
    @endforelse
</div>
<div class="mt-4">{{ $articles->links() }}</div>
@endsection
