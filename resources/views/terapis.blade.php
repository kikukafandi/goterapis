@extends('layouts.app')
@section('title', $therapist->user->name.' — Terapis di '.$therapist->city)
@section('meta', 'Lihat layanan, harga, jadwal, dan profil '.$therapist->user->name.', terapis di '.$therapist->city.'.')
@section('canonical', route('terapis.show', $therapist))
@section('image', $therapist->user->avatarUrl() ? url($therapist->user->avatarUrl()) : asset('images/brand/logo-mark.png'))
@section('type', 'profile')
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'ProfilePage', 'url' => route('terapis.show', $therapist), 'mainEntity' => ['@type' => 'Person', 'name' => $therapist->user->name, 'image' => $therapist->user->avatarUrl() ? url($therapist->user->avatarUrl()) : null, 'jobTitle' => 'Terapis', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $therapist->city, 'addressRegion' => $therapist->province], 'knowsAbout' => $therapist->services->pluck('name')->values()]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@php
    $u = $therapist->user;
    $statusLabel = $therapist->statusLabel();
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 pb-28 pt-5 sm:pt-7">
    <nav class="mb-5 flex items-center gap-2 text-xs text-kabut" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-daun">Beranda</a><span>/</span>
        <a href="{{ route('cari') }}" class="hover:text-daun">Cari terapis</a><span>/</span>
        <span class="truncate font-semibold text-arang">{{ $u->name }}</span>
    </nav>

    <div class="h-36 rounded-card bg-daun-muda sm:h-56"></div>

    <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_360px]">
        <main class="min-w-0 space-y-5">
            <section class="relative -mt-12 rounded-card border border-garis bg-white p-5 sm:-mt-16 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="relative w-fit shrink-0">
                        <img src="{{ $u->avatarUrl() ?? 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=E7EFE6&color=2E5A39' }}" alt="{{ $u->name }}" class="h-24 w-24 rounded-2xl border-4 border-white object-cover sm:h-28 sm:w-28">
                        <x-badge :status="$therapist->verification_status" size="h-9 w-9" class="absolute -bottom-1 -right-1" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="font-display text-2xl font-bold tracking-tight text-arang sm:text-3xl">{{ $u->name }}</h1>
                        <p class="mt-1 text-sm text-kabut">{{ $therapist->services->pluck('name')->take(2)->join(' · ') }} · {{ $therapist->city }}</p>
                    </div>
                    <span class="w-fit rounded-xl border border-daun bg-daun-muda px-3 py-2 text-xs font-bold text-daun-tua">{{ $statusLabel }}</span>
                </div>
                <dl class="mt-5 grid grid-cols-3 border-t border-garis pt-5">
                    <div><dt class="text-xs text-kabut">Rating</dt><dd class="mt-1 text-xl font-bold text-arang">{{ number_format($therapist->rating_avg, 1, ',', '') }} <span class="text-sm text-kunyit">★</span></dd></div>
                    <div><dt class="text-xs text-kabut">Ulasan</dt><dd class="mt-1 text-xl font-bold text-arang">{{ $therapist->reviews_count }}</dd></div>
                    <div><dt class="text-xs text-kabut">Pengalaman</dt><dd class="mt-1 text-xl font-bold text-arang">{{ $therapist->experience_years }} <span class="text-sm">tahun</span></dd></div>
                </dl>
            </section>

            <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
                <h2 class="font-display text-lg font-bold text-arang">Tentang</h2>
                @if ($therapist->bio)<p class="mt-3 text-sm leading-7 text-kabut">{{ $therapist->bio }}</p>@else<p class="mt-3 text-sm text-kabut">Terapis belum menambahkan deskripsi.</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($therapist->serves_call)<span class="chip">Panggilan</span>@endif
                    @if ($therapist->serves_place)<span class="chip">Tempat praktik</span>@endif
                    <span class="chip">{{ $therapist->district ? $therapist->district.', ' : '' }}{{ $therapist->city }}</span>
                </div>
            </section>

            <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
                <h2 class="font-display text-lg font-bold text-arang">Layanan & harga</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($therapist->services as $s)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-garis p-4">
                            <div class="min-w-0"><p class="font-semibold text-arang">{{ $s->name }}</p><p class="mt-1 text-xs text-kabut">{{ $s->pivot->duration_min }} menit</p></div>
                            <p class="shrink-0 text-sm font-bold text-arang">Rp{{ number_format($s->pivot->price, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-card border border-garis bg-white p-5">
                    <h2 class="flex items-center gap-2 font-display font-bold text-arang"><x-icon name="pin" class="h-5 w-5 text-daun" /> Wilayah</h2>
                    <p class="mt-3 text-sm leading-6 text-kabut">{{ $therapist->district ? $therapist->district.', ' : '' }}{{ $therapist->city }}{{ $therapist->province ? ', '.$therapist->province : '' }}</p>
                    @if ($therapist->serves_call && $therapist->transport_fee)<p class="mt-2 text-sm text-kabut">Transport <strong class="text-arang">Rp{{ number_format($therapist->transport_fee, 0, ',', '.') }}</strong></p>@endif
                </div>
                <div class="rounded-card border border-garis bg-white p-5">
                    <h2 class="flex items-center gap-2 font-display font-bold text-arang"><x-icon name="calendar" class="h-5 w-5 text-daun" /> Jadwal</h2>
                    @if ($therapist->schedules->isNotEmpty())
                        <ul class="mt-3 space-y-1 text-sm text-kabut">@foreach ($therapist->schedules as $sc)<li class="flex justify-between gap-3"><span>{{ $days[$sc->day_of_week] ?? '' }}</span><span class="font-semibold text-arang">{{ substr($sc->start_time, 0, 5) }}–{{ substr($sc->end_time, 0, 5) }}</span></li>@endforeach</ul>
                    @else<p class="mt-3 text-sm leading-6 text-kabut">Jadwal fleksibel — pilih waktu saat memesan.</p>@endif
                </div>
            </section>

            <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
                <div class="flex items-baseline justify-between gap-4"><h2 class="font-display text-lg font-bold text-arang">Ulasan</h2><p class="text-xs text-kabut">{{ number_format($therapist->rating_avg, 1, ',', '') }} ★ dari {{ $therapist->reviews_count }} ulasan</p></div>
                @forelse ($therapist->reviews->where('is_hidden', false) as $r)
                    <article class="mt-5 border-t border-garis pt-5 first:border-0 first:pt-1">
                        <div class="flex items-center justify-between gap-3"><p class="font-semibold text-arang">{{ $r->user->name }}</p><p class="text-sm text-kunyit">@for ($i = 0; $i < 5; $i++)<span class="{{ $i < round($r->averageRating()) ? '' : 'text-garis' }}">★</span>@endfor</p></div>
                        @if ($r->body)<p class="mt-2 text-sm leading-6 text-kabut">{{ $r->body }}</p>@endif
                    </article>
                @empty<p class="mt-4 text-sm text-kabut">Belum ada ulasan. Jadilah yang pertama setelah memesan.</p>@endforelse
            </section>
        </main>

        <aside class="hidden rounded-card border border-garis bg-white p-6 shadow-sm lg:sticky lg:top-24 lg:block">
            <p class="text-xs text-kabut">Harga mulai</p>
            <p class="mt-1 font-display text-2xl font-bold text-arang">Rp{{ number_format($therapist->services->min('pivot.price') ?? 0, 0, ',', '.') }}</p>
            <div class="mt-5 space-y-3 border-t border-garis pt-5 text-sm text-kabut">
                <p class="flex items-center gap-2"><x-icon name="calendar" class="h-5 w-5 text-daun" /> Atur jadwal saat memesan</p>
                <p class="flex items-center gap-2"><x-icon name="pin" class="h-5 w-5 text-daun" /> {{ $therapist->serves_call ? 'Tersedia layanan panggilan' : 'Layanan di tempat praktik' }}</p>
            </div>
            <a href="{{ route('pesan.create', $therapist) }}" class="mt-6 block rounded-xl bg-daun px-6 py-4 text-center text-sm font-bold text-white transition-colors hover:bg-daun-tua">Pesan sekarang</a>
            <p class="mt-3 text-center text-xs leading-5 text-kabut">Pilih layanan dan jadwal pada langkah berikutnya.</p>
        </aside>
    </div>
</div>

<div class="fixed inset-x-0 bottom-16 z-30 border-t border-garis bg-white px-4 py-3 md:bottom-0 lg:hidden">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-4">
        <div><p class="text-xs text-kabut">mulai</p><p class="text-lg font-bold text-arang">Rp{{ number_format($therapist->services->min('pivot.price') ?? 0, 0, ',', '.') }}</p></div>
        <a href="{{ route('pesan.create', $therapist) }}" class="flex-1 rounded-xl bg-daun px-6 py-3 text-center text-sm font-bold text-white sm:flex-none">Pesan sekarang</a>
    </div>
</div>
@endsection
