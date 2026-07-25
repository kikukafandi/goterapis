@extends('layouts.app')
@section('title', 'Info Sehat GoTerapis')
@section('meta', 'Catatan tentang pijat, bekam, dan perawatan tubuh tradisional.')

@section('content')
<div class="mx-auto max-w-5xl px-5 pb-8 pt-8 sm:pt-14">

    <header class="max-w-2xl">
        <div class="flex items-center gap-2.5 text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-daun">
            <span class="h-px w-6 bg-kunyit"></span> Info Sehat GoTerapis
        </div>
        <h1 class="mt-5 font-serif text-4xl font-medium leading-[1.05] tracking-[-0.01em] text-arang sm:text-6xl">Info Sehat</h1>
        <p class="mt-4 font-serif text-lg italic leading-relaxed text-kabut sm:text-xl">
            Catatan tentang pijat, bekam, dan cara merawat tubuh dengan tradisi yang diwariskan.
        </p>
    </header>

    @php $featured = $articles->first(); @endphp

    @if ($featured)
        {{-- Sorotan terbaru --}}
        <a href="{{ route('artikel.show', $featured) }}"
           class="group mt-12 grid gap-6 border-t border-garis pt-10 sm:grid-cols-2 sm:items-center">
            @if ($featured->coverUrl())
                <img src="{{ $featured->coverUrl() }}" alt="" class="w-full rounded-card object-cover" style="aspect-ratio: 3 / 2;">
            @else
                <div class="grid w-full place-items-center rounded-card bg-daun-muda" style="aspect-ratio: 3 / 2;">
                    <x-icon name="leaf" class="h-10 w-10 text-daun/50" />
                </div>
            @endif
            <div>
                <p class="text-xs uppercase tracking-[0.14em] text-kabut">Terbaru</p>
                <h2 class="mt-2 font-serif text-3xl font-medium leading-[1.1] tracking-[-0.01em] text-arang transition-colors group-hover:text-daun">{{ $featured->title }}</h2>
                @if ($featured->excerpt)
                    <p class="mt-3 leading-relaxed text-kabut">{{ $featured->excerpt }}</p>
                @endif
                <p class="mt-4 text-xs uppercase tracking-[0.12em] text-kabut">
                    {{ $featured->author->name }}
                    <span class="mx-1.5 text-kunyit">&bull;</span>
                    {{ $featured->readingMinutes() }} menit baca
                </p>
            </div>
        </a>

        {{-- Sisanya --}}
        @php $rest = $articles->slice(1); @endphp
        @if ($rest->isNotEmpty())
            <div class="mt-12 grid gap-x-6 gap-y-10 border-t border-garis pt-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rest as $article)
                    <a href="{{ route('artikel.show', $article) }}" class="group block">
                        @if ($article->coverUrl())
                            <img src="{{ $article->coverUrl() }}" alt="" class="mb-4 w-full rounded-xl object-cover" style="aspect-ratio: 3 / 2;">
                        @else
                            <div class="mb-4 grid w-full place-items-center rounded-xl bg-daun-muda" style="aspect-ratio: 3 / 2;">
                                <x-icon name="leaf" class="h-8 w-8 text-daun/50" />
                            </div>
                        @endif
                        <h3 class="font-serif text-xl font-medium leading-snug text-arang transition-colors group-hover:text-daun">{{ $article->title }}</h3>
                        @if ($article->excerpt)
                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-kabut">{{ $article->excerpt }}</p>
                        @endif
                        <p class="mt-3 text-xs uppercase tracking-[0.12em] text-kabut">{{ $article->readingMinutes() }} menit baca</p>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-12">{{ $articles->links() }}</div>
    @else
        <div class="mt-16 border-t border-garis py-20 text-center">
            <x-icon name="leaf" class="mx-auto h-8 w-8 text-daun/40" />
            <p class="mt-4 font-serif text-xl italic text-kabut">Info Sehat masih kosong. Nantikan tulisan pertama kami.</p>
        </div>
    @endif
</div>
@endsection
