@extends('layouts.app')
@section('title', $document['title'].' — GoTerapis')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10 sm:py-14">
    @if ($incomplete)
        <div role="alert" class="mb-6 rounded-card border border-jahe bg-jahe/10 p-4 text-sm text-arang">
            <p class="font-semibold text-jahe">Draf dokumen hukum — belum siap untuk produksi</p>
            <p class="mt-1">Identitas operator, versi, atau tanggal berlaku belum lengkap. Isi konfigurasi legal sebelum menerbitkan layanan.</p>
        </div>
    @endif

    <header class="border-b border-garis pb-6">
        <p class="text-xs font-bold uppercase tracking-wider text-daun">Dokumen hukum</p>
        <h1 class="mt-2 font-display text-3xl font-bold text-arang sm:text-4xl">{{ $document['title'] }}</h1>
        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-kabut">
            <div><dt class="inline font-semibold text-arang">Versi:</dt> <dd class="inline">{{ config('legal.version') }}</dd></div>
            <div><dt class="inline font-semibold text-arang">Berlaku:</dt> <dd class="inline">{{ config('legal.effective_date') }}</dd></div>
        </dl>
    </header>

    <div class="mt-8 space-y-8">
        @foreach ($document['sections'] as [$heading, $body])
            <section>
                <h2 class="font-display text-xl font-semibold text-arang">{{ $heading }}</h2>
                <p class="mt-2 leading-7 text-arang/80">{{ $body }}</p>
            </section>
        @endforeach
    </div>
</article>
@endsection
