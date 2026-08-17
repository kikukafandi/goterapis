@extends('layouts.app')

@php
    $steps = [
        ['Pilih terapis', 'Bandingkan profil, layanan, harga, dan ulasan terapis di kotamu.'],
        ['Atur jadwal & alamat', 'Pilih waktu yang tersedia dan tentukan lokasi layanan.'],
        ['Terapis datang', 'Bayar setelah pesanan diterima, lalu terapis menuju lokasimu.'],
    ];
@endphp

@section('content')
<div class="mx-auto flex max-w-6xl flex-col gap-14 px-4 pb-16 pt-5 sm:pt-9">
    <section class="grid gap-5 lg:grid-cols-[1.05fr_.95fr] lg:gap-9">
        <div class="relative flex min-h-[340px] flex-col justify-center overflow-hidden rounded-[28px] bg-daun-terang px-6 py-10 sm:px-10 sm:py-11">
            <span class="absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-white/10"></span>
            @if ($heroEyebrow = \App\Models\Setting::get('hero_eyebrow'))
                <p class="relative text-xs font-semibold uppercase tracking-[.04em] text-white/85">{{ $heroEyebrow }}</p>
            @endif
            <h1 class="font-display relative mt-5 max-w-xl text-[34px] font-extrabold leading-[1.1] text-white sm:text-[42px]">{{ \App\Models\Setting::get('hero_title') }}</h1>
            @if ($heroSubtitle = \App\Models\Setting::get('hero_subtitle'))
                <p class="relative mt-5 max-w-md text-[15px] font-medium leading-relaxed text-white/90">{{ $heroSubtitle }}</p>
            @endif
            <div class="relative mt-6 flex flex-wrap gap-3">
                <a href="{{ route('cari') }}" class="rounded-[14px] bg-white px-6 py-4 text-sm font-bold text-daun hover:text-daun-tua">{{ \App\Models\Setting::get('hero_cta_utama') }}</a>
                {{-- Terapis tak perlu diajak mendaftar lagi — arahkan ke panel mitranya. --}}
                @if (auth()->user()?->isTherapist())
                    <a href="{{ route('mitra.dashboard') }}" class="rounded-[14px] border-2 border-white/50 px-6 py-4 text-sm font-bold text-white hover:border-white">{{ \App\Models\Setting::get('hero_cta_panel') }}</a>
                @else
                    <a href="{{ route('register.therapist') }}" class="rounded-[14px] border-2 border-white/50 px-6 py-4 text-sm font-bold text-white hover:border-white">{{ \App\Models\Setting::get('hero_cta_mitra') }}</a>
                @endif
            </div>
        </div>
        <div class="min-h-[280px] overflow-hidden rounded-[28px] lg:min-h-[340px]">
            <img src="{{ \App\Models\Setting::imageUrl('hero_image', asset('images/hero.webp')) }}" alt="Sesi terapi GoTerapis" fetchpriority="high" class="h-full w-full object-cover">
        </div>
    </section>

    <section>
        <div class="mb-5 flex items-baseline justify-between">
            <h2 class="font-display text-[26px] font-extrabold text-arang">Kategori layanan</h2>
            <a href="{{ route('cari') }}" class="text-[13px] font-bold text-daun">Lihat semua</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('cari', ['kategori' => $category->slug]) }}" class="flex flex-col items-center gap-3 rounded-card border border-garis bg-white px-3 py-5 text-center hover:border-daun-terang">
                    <span class="h-[62px] w-[62px] overflow-hidden rounded-full bg-garis-muda"><x-cat-icon :slug="$category->slug" :src="$category->iconUrl()" class="h-full w-full" /></span>
                    <span class="text-[13px] font-semibold text-arang">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-5 flex items-baseline justify-between">
            <h2 class="font-display text-[26px] font-extrabold text-arang">Terapis pilihan</h2>
            <a href="{{ route('cari') }}" class="text-[13px] font-bold text-daun">Lihat semua</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
            @forelse ($therapists->take(4) as $therapist)
                @php($service = $therapist->services->first())
                <a href="{{ route('terapis.show', $therapist) }}" class="flex min-w-0 flex-col gap-2.5 rounded-[22px] border border-garis bg-white p-2.5 hover:border-daun-garis sm:gap-3.5 sm:p-[13px]">
                    <span class="relative block h-28 overflow-hidden rounded-[14px] bg-garis-muda sm:h-[170px] sm:rounded-2xl">
                        @if ($therapist->user->avatarUrl())
                            <img src="{{ $therapist->user->avatarUrl() }}" alt="{{ $therapist->user->name }}" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="grid h-full place-items-center text-4xl font-extrabold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
                        @endif
                        <span class="absolute left-2 top-2 inline-flex max-w-[calc(100%-1rem)] items-center gap-1 rounded-full bg-white/95 px-2 py-1 text-[8px] font-bold text-daun sm:left-2.5 sm:top-2.5 sm:gap-1.5 sm:px-2.5 sm:py-1.5 sm:text-[10px]">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-daun-terang sm:h-2.5 sm:w-2.5"></span><span class="truncate">{{ $therapist->statusLabel() }}</span>
                        </span>
                    </span>
                    <span class="flex min-w-0 flex-col gap-1 px-1 pb-1 sm:gap-1.5">
                        <span class="truncate text-[13px] font-bold text-arang sm:text-base">{{ $therapist->user->name }}</span>
                        <span class="truncate text-[10px] font-medium text-kabut-muda sm:text-xs">{{ $service?->name ?? 'Layanan terapi' }}</span>
                        <span class="flex min-w-0 items-center gap-1 text-[10px] sm:gap-1.5 sm:text-xs"><span class="font-bold text-kunyit">★</span><strong class="text-arang">{{ number_format($therapist->rating_avg, 1) }}</strong><span class="truncate text-kabut-samar">({{ $therapist->reviews_count }}) · {{ $therapist->city }}</span></span>
                        <span class="mt-1 flex flex-col text-[9px] text-kabut-samar sm:block sm:text-[11px]">mulai <strong class="font-display block text-[13px] font-extrabold text-arang sm:inline sm:text-[17px]">Rp{{ number_format((int) ($service?->pivot?->price ?? 0), 0, ',', '.') }}</strong></span>
                    </span>
                </a>
            @empty
                <div class="kartu col-span-full p-8 text-center text-sm text-kabut">Belum ada terapis yang tersedia.</div>
            @endforelse
        </div>
    </section>

    <section id="cara-kerja" class="rounded-[28px] bg-malam px-6 py-10 sm:px-10 sm:py-11">
        <h2 class="font-display text-[26px] font-extrabold text-white">Cara kerja</h2>
        <div class="mt-8 grid gap-8 sm:grid-cols-3">
            @foreach ($steps as $index => [$title, $description])
                <div class="flex flex-col gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-white/10 text-[17px] font-extrabold text-daun-neon">{{ $index + 1 }}</span>
                    <h3 class="text-[17px] font-bold text-white">{{ $title }}</h3>
                    <p class="text-[13px] font-medium leading-relaxed text-white/55">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-5 flex items-baseline justify-between">
            <h2 class="font-display text-[26px] font-extrabold text-arang">Jurnal kesehatan</h2>
            <a href="{{ route('artikel.index') }}" class="text-[13px] font-bold text-daun">Lihat semua</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            @forelse ($articles as $article)
                <a href="{{ route('artikel.show', $article) }}" class="overflow-hidden rounded-[22px] border border-garis bg-white">
                    @if ($article->coverUrl())
                        <img src="{{ $article->coverUrl() }}" alt="" loading="lazy" class="h-40 w-full object-cover">
                    @else
                        <span class="grid h-40 place-items-center bg-garis-muda text-kabut-samar"><x-icon name="leaf" class="h-8 w-8" /></span>
                    @endif
                    <span class="flex flex-col gap-2 p-5">
                        <span class="text-[10px] font-bold uppercase tracking-[.06em] text-daun">Jurnal kesehatan</span>
                        <span class="text-[17px] font-bold leading-snug text-arang">{{ $article->title }}</span>
                        <span class="text-xs font-medium text-kabut-samar">{{ $article->readingMinutes() }} menit baca</span>
                    </span>
                </a>
            @empty
                <p class="col-span-full rounded-card border border-garis bg-white p-8 text-center text-sm text-kabut">Belum ada jurnal yang diterbitkan.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
