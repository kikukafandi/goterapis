@extends('layouts.app')
@php
    $categoryLabel = str($category)->headline();
    $isMassage = $category === 'pijat';
    $heading = ($isMassage ? 'Tukang Pijat' : 'Terapis '.$categoryLabel).' di '.$city;
    $description = 'Temukan '.strtolower($heading).' yang tersedia dan telah memenuhi kelayakan profil GoTerapis. Bandingkan layanan, harga, dan jadwal sebelum memesan.';
    $canonical = route('seo.local', [$category, Str::slug($city)]);
    $items = $therapists->map(fn ($therapist, $index) => ['@type' => 'ListItem', 'position' => ($therapists->currentPage() - 1) * $therapists->perPage() + $index + 1, 'url' => route('terapis.show', $therapist), 'name' => $therapist->user->name]);
    $schema = ['@context' => 'https://schema.org', '@graph' => [['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')], ['@type' => 'ListItem', 'position' => 2, 'name' => $heading, 'item' => $canonical]]], ['@type' => 'Service', 'name' => $heading, 'serviceType' => $categoryLabel, 'areaServed' => ['@type' => 'City', 'name' => $city], 'provider' => ['@type' => 'Organization', 'name' => 'GoTerapis', 'url' => route('home')]], ['@type' => 'ItemList', 'name' => $heading, 'numberOfItems' => $therapists->total(), 'itemListElement' => $items]]];
@endphp
@section('title', $heading)
@section('meta', $description)
@section('canonical', $canonical)
@section('robots', $therapists->currentPage() > 1 ? 'noindex, follow' : 'index, follow')
@push('head')
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@section('content')
<div class="mx-auto max-w-6xl px-4 pb-16 pt-6 sm:pt-10">
    <nav class="flex items-center gap-2 text-xs text-kabut" aria-label="Breadcrumb"><a href="{{ route('home') }}" class="hover:text-daun">Beranda</a><span>/</span><span>{{ $heading }}</span></nav>
    <header class="mt-6 rounded-card bg-daun-muda p-6 sm:p-9">
        <h1 class="font-display text-3xl font-extrabold text-arang sm:text-4xl">{{ $heading }}</h1>
        <p class="mt-4 max-w-3xl leading-7 text-kabut">{{ $description }}</p>
    </header>
    <p class="mt-8 text-sm font-semibold text-kabut">{{ $therapists->total() }} terapis tersedia</p>
    <section class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Daftar terapis">
        @foreach ($therapists as $therapist)
            <a href="{{ route('terapis.show', $therapist) }}" class="flex gap-4 rounded-card border border-garis bg-white p-4 hover:border-daun">
                @if ($therapist->user->avatarUrl())<img src="{{ $therapist->user->avatarUrl() }}" alt="Foto {{ $therapist->user->name }}" loading="lazy" class="h-20 w-20 rounded-2xl object-cover">@endif
                <span class="min-w-0"><strong class="font-display text-lg text-arang">{{ $therapist->user->name }}</strong><span class="mt-1 block text-sm text-kabut">{{ $therapist->services->where('category', $category)->pluck('name')->take(2)->join(' · ') }}</span><span class="mt-2 block text-xs font-semibold text-daun">{{ $therapist->city }}</span></span>
            </a>
        @endforeach
    </section>
    {{ $therapists->links() }}
    @if ($related->isNotEmpty())
        <aside class="mt-12 border-t border-garis pt-8"><h2 class="font-display text-xl font-bold text-arang">Layanan di kota lain</h2><div class="mt-4 flex flex-wrap gap-2">@foreach ($related as $item)<a class="rounded-full border border-garis bg-white px-4 py-2 text-sm font-semibold text-daun" href="{{ route('seo.local', [$item['category'], Str::slug($item['city'])]) }}">{{ str($item['category'])->headline() }} di {{ $item['city'] }}</a>@endforeach</div></aside>
    @endif
</div>
@endsection
