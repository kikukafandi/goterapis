@extends('layouts.app')
@section('title', $article->meta_title ?? $article->title)
@section('meta', $article->excerpt ?? 'Info Sehat GoTerapis')

@push('head')
    <style>
        .jurnal-prose { font-family: var(--font-serif); color: var(--color-arang); }
        .jurnal-prose > p { margin: 0 0 1.5rem; font-size: 1.125rem; line-height: 1.85; }
        @media (min-width: 640px) { .jurnal-prose > p { font-size: 1.25rem; } }
        .jurnal-prose > p:first-of-type::first-letter {
            float: left; margin: .4rem .75rem 0 0; font-weight: 500;
            font-size: 4.25rem; line-height: .72; color: var(--color-kunyit);
        }
        .jurnal-prose h2 { margin: 3rem 0 1rem; font-size: 1.75rem; font-weight: 500; line-height: 1.25; letter-spacing: -.01em; color: var(--color-daun-tua); }
        .jurnal-prose h3 { margin: 2rem 0 .75rem; font-size: 1.35rem; font-weight: 500; color: var(--color-daun-tua); }
        .jurnal-prose blockquote { margin: 2.5rem 0; border-left: 2px solid var(--color-kunyit); padding-left: 1.5rem; font-size: 1.5rem; font-style: italic; line-height: 1.4; color: var(--color-daun); }
        .jurnal-prose ul, .jurnal-prose ol { margin: 0 0 1.5rem; padding-left: 1.5rem; font-size: 1.125rem; line-height: 1.8; }
        .jurnal-prose ul { list-style: disc; }
        .jurnal-prose ol { list-style: decimal; }
        .jurnal-prose li { margin: .4rem 0; }
        .jurnal-prose a { color: var(--color-daun); text-decoration: underline; text-underline-offset: 2px; }
        .jurnal-prose strong { font-weight: 600; }
        .jurnal-prose mark { background: var(--color-kunyit-muda); padding: 0 .15em; }
        .jurnal-prose figure { margin: 2.5rem 0; }
        .jurnal-prose img { width: 100%; height: auto; border-radius: var(--radius-card); }
        .jurnal-prose figure.image-style-side { float: right; width: 50%; margin: .5rem 0 1.5rem 2rem; }
        .jurnal-prose figcaption { margin-top: .6rem; font-family: var(--font-sans); font-size: .85rem; color: var(--color-kabut); text-align: center; }
        .jurnal-prose hr { margin: 3rem 0; border: 0; border-top: 1px solid var(--color-garis); }
        .jurnal-prose table { width: 100%; margin: 2rem 0; border-collapse: collapse; font-size: 1rem; }
        .jurnal-prose th, .jurnal-prose td { border: 1px solid var(--color-garis); padding: .6rem .8rem; text-align: left; }
        .jurnal-prose th { background: var(--color-daun-muda); font-weight: 600; }
        .jurnal-prose pre { margin: 2rem 0; overflow-x: auto; border-radius: .75rem; background: var(--color-arang); color: #f4f4ef; padding: 1rem 1.25rem; font-size: .9rem; }
        .jurnal-prose :not(pre) > code { font-family: ui-monospace, monospace; font-size: .9em; background: var(--color-daun-muda); padding: .1em .35em; border-radius: .3rem; }
    </style>
@endpush

@section('content')
<article class="mx-auto max-w-2xl px-5 pb-4 pt-6 sm:pt-10">

    <a href="{{ route('artikel.index') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-kabut transition-colors hover:text-daun">
        <x-icon name="arrow-left" class="h-4 w-4" /> Info Sehat
    </a>

    <header class="mt-8">
        {{-- Eyebrow: dua garis kunyit mengapit label --}}
        <div class="flex items-center gap-2.5 text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-daun">
            <span class="h-px w-6 bg-kunyit"></span>
            Info Sehat GoTerapis
        </div>

        <h1 class="mt-5 font-serif text-4xl font-medium leading-[1.08] tracking-[-0.01em] text-arang sm:text-5xl">
            {{ $article->title }}
        </h1>

        @if ($article->excerpt)
            <p class="mt-5 font-serif text-xl italic leading-relaxed text-kabut sm:text-2xl">
                {{ $article->excerpt }}
            </p>
        @endif

        {{-- Rail penulis --}}
        <div class="mt-8 flex items-center gap-3 border-y border-garis py-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-daun-muda font-display text-lg font-semibold text-daun">
                {{ mb_substr($article->author->name, 0, 1) }}
            </span>
            <div class="text-sm">
                <p class="font-semibold text-arang">{{ $article->author->name }}</p>
                <p class="text-xs uppercase tracking-[0.12em] text-kabut">
                    {{ $article->published_at->translatedFormat('d F Y') }}
                    <span class="mx-1.5 text-kunyit">&bull;</span>
                    {{ $article->readingMinutes() }} menit baca
                </p>
            </div>
        </div>
    </header>

    @if ($article->coverUrl())
        <figure class="mt-8">
            <img src="{{ $article->coverUrl() }}" alt="{{ $article->title }}"
                 class="w-full rounded-card object-cover" style="aspect-ratio: 3 / 2;">
        </figure>
    @endif

    {{-- Tubuh redaksional --}}
    <div class="jurnal-prose mt-10">
        {!! $article->body !!}
    </div>

    {{-- Penutup --}}
    <div class="my-12 flex items-center justify-center gap-4" aria-hidden="true">
        <span class="h-px w-16 bg-garis"></span>
        <x-icon name="leaf" class="h-5 w-5 text-kunyit" />
        <span class="h-px w-16 bg-garis"></span>
    </div>

    <div class="flex items-center gap-3 rounded-card bg-daun-muda/60 p-5">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-daun font-display text-lg font-semibold text-white">
            {{ mb_substr($article->author->name, 0, 1) }}
        </span>
        <div class="text-sm">
            <p class="text-xs uppercase tracking-[0.14em] text-kabut">Ditulis oleh</p>
            <p class="font-semibold text-arang">{{ $article->author->name }}</p>
        </div>
    </div>
</article>

@if ($more->isNotEmpty())
    <section class="mx-auto mt-16 max-w-2xl px-5">
        <h2 class="flex items-center gap-2.5 text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-daun">
            <span class="h-px w-6 bg-kunyit"></span> Baca juga
        </h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            @foreach ($more as $m)
                <a href="{{ route('artikel.show', $m) }}" class="group block">
                    @if ($m->coverUrl())
                        <img src="{{ $m->coverUrl() }}" alt="" class="mb-3 w-full rounded-xl object-cover" style="aspect-ratio: 3 / 2;">
                    @endif
                    <h3 class="font-serif text-lg font-medium leading-snug text-arang transition-colors group-hover:text-daun">{{ $m->title }}</h3>
                    <p class="mt-1 text-xs uppercase tracking-[0.12em] text-kabut">{{ $m->readingMinutes() }} menit baca</p>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection
