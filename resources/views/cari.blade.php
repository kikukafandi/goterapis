@extends('layouts.app')
@section('title', 'Cari Terapis')

@php
    $statusLabels = \App\Models\TherapistProfile::STATUS_LABELS;
    $u = 'https://images.unsplash.com/';
@endphp

@section('content')
{{-- ===== Bar pencarian (latar foto + overlay hijau, sama seperti landing) ===== --}}
<section class="relative isolate overflow-hidden border-b border-garis">
    <img src="{{ $u }}photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=70&w=1600&h=500"
         alt="" aria-hidden="true" loading="eager"
         class="absolute inset-0 -z-10 h-full w-full object-cover">
    <div class="absolute inset-0 -z-10 bg-daun/75"></div>
    <div class="mx-auto max-w-6xl px-4 py-5">
        <form action="{{ route('cari') }}" method="get" class="rounded-card bg-white p-2 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row">
                <label class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2.5 focus-within:bg-kertas">
                    <x-icon name="search" class="h-5 w-5 shrink-0 text-daun" />
                    <input name="q" value="{{ $q }}" type="text" placeholder="Layanan atau nama terapis"
                           class="w-full bg-transparent text-sm outline-none placeholder:text-kabut">
                </label>
                <label class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2.5 focus-within:bg-kertas sm:border-l sm:border-garis">
                    <x-icon name="pin" class="h-5 w-5 shrink-0 text-daun" />
                    <select name="kota" class="w-full cursor-pointer appearance-none bg-transparent text-sm outline-none">
                        <option value="">Semua kota</option>
                        @foreach ($cities as $c)
                            <option value="{{ $c }}" @selected($kota === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-kabut" />
                </label>
                @if ($kategori)<input type="hidden" name="kategori" value="{{ $kategori }}">@endif
                @if ($model)<input type="hidden" name="model" value="{{ $model }}">@endif
                <button class="rounded-xl bg-daun px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">Cari</button>
            </div>
        </form>
    </div>
</section>

{{-- ===== Filter kategori + model ===== --}}
<section class="sticky top-[57px] z-30 border-b border-garis bg-kertas/95 backdrop-blur">
    <div class="mx-auto max-w-6xl px-4 py-3">
        <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <a href="{{ request()->fullUrlWithQuery(['kategori' => null, 'page' => null]) }}"
               class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors {{ ! $kategori ? 'border-daun bg-daun text-white' : 'border-garis bg-white text-arang hover:border-daun' }}">Semua</a>
            @foreach ($categories as $slug => $label)
                <a href="{{ request()->fullUrlWithQuery(['kategori' => $slug, 'page' => null]) }}"
                   class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors {{ $kategori === $slug ? 'border-daun bg-daun text-white' : 'border-garis bg-white text-arang hover:border-daun' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== Hasil ===== --}}
<section class="mx-auto max-w-6xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-kabut">
            <span class="font-semibold text-arang">{{ $therapists->total() }} terapis</span>
            @if ($kota) di {{ $kota }} @endif
            @if ($q) untuk “{{ $q }}” @endif
        </p>
        <div class="flex items-center gap-2">
            {{-- Model --}}
            <div class="hidden items-center gap-1 rounded-full border border-garis bg-white p-1 sm:flex">
                @foreach (['' => 'Semua', 'panggilan' => 'Panggilan', 'tempat' => 'Tempat'] as $val => $lbl)
                    <a href="{{ request()->fullUrlWithQuery(['model' => $val ?: null, 'page' => null]) }}"
                       class="rounded-full px-3 py-1 text-xs font-semibold {{ ($model ?: '') === $val ? 'bg-daun text-white' : 'text-arang hover:bg-kertas' }}">{{ $lbl }}</a>
                @endforeach
            </div>
            {{-- Urutkan --}}
            <form action="{{ route('cari') }}" method="get">
                @foreach (['q' => $q, 'kategori' => $kategori, 'kota' => $kota, 'model' => $model] as $k => $v)
                    @if ($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <div class="relative">
                    <select name="sort" onchange="this.form.submit()"
                            class="cursor-pointer appearance-none rounded-full border border-garis bg-white py-2 pl-3 pr-8 text-xs font-semibold text-arang outline-none focus:border-daun">
                        <option value="rekomendasi" @selected($sort === 'rekomendasi')>Rekomendasi</option>
                        <option value="rating" @selected($sort === 'rating')>Rating tertinggi</option>
                        <option value="termurah" @selected($sort === 'termurah')>Harga termurah</option>
                        <option value="terlaris" @selected($sort === 'terlaris')>Terlaris</option>
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-kabut" />
                </div>
            </form>
        </div>
    </div>

    @if ($therapists->isEmpty())
        <div class="mt-8 rounded-card border border-dashed border-garis bg-white p-12 text-center">
            <p class="font-semibold text-arang">Belum ada terapis yang cocok</p>
            <p class="mt-1 text-sm text-kabut">Coba ubah kata kunci, kota, atau kategori.</p>
            <a href="{{ route('cari') }}" class="mt-5 inline-block rounded-full bg-daun px-5 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">Reset pencarian</a>
        </div>
    @else
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach ($therapists as $t)
                <a href="{{ route('terapis.show', $t) }}"
                   class="group flex gap-4 rounded-card border border-garis bg-white p-4 transition-shadow hover:shadow-md">
                    {{-- Avatar --}}
                    <div class="relative shrink-0">
                        <img src="{{ $t->user->avatarUrl() ?? 'https://ui-avatars.com/api/?name='.urlencode($t->user->name).'&background=E7EFE6&color=2E5A39' }}"
                             alt="{{ $t->user->name }}" loading="lazy"
                             class="h-20 w-20 rounded-2xl object-cover">
                        <x-badge :status="$t->verification_status" size="h-8 w-8"
                                 class="absolute -bottom-2 -right-2 drop-shadow-sm" />
                    </div>

                    {{-- Info --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-arang">{{ $t->user->name }}</h3>
                                <p class="mt-0.5 flex items-center gap-1 text-sm">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-kunyit" fill="currentColor"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                                    <span class="font-semibold text-arang">{{ number_format($t->rating_avg, 1, ',', '') }}</span>
                                    <span class="text-kabut">({{ $t->reviews_count }})</span>
                                    <span class="text-garis">·</span>
                                    <span class="text-kabut">{{ $t->experience_years }} th</span>
                                </p>
                            </div>
                            @if ($t->verification_status === 'pilihan')
                                <span class="shrink-0 rounded-full bg-kunyit-muda px-2 py-0.5 text-[11px] font-semibold text-kunyit">★ Pilihan</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($t->services->take(2) as $s)
                                <span class="chip">{{ $s->name }}</span>
                            @endforeach
                            @if ($t->services->count() > 2)
                                <span class="chip">+{{ $t->services->count() - 2 }}</span>
                            @endif
                        </div>

                        <div class="mt-3 flex items-end justify-between gap-2">
                            <p class="flex items-center gap-1 text-xs text-kabut">
                                <x-icon name="pin" class="h-4 w-4" /> {{ $t->city }}
                                <span class="text-garis">·</span>
                                {{ $t->serves_call ? 'Panggilan' : 'Tempat praktik' }}
                            </p>
                            <p class="text-right text-xs text-kabut">mulai<br><span class="text-sm font-bold text-arang">Rp{{ number_format($t->starting_price ?? 0, 0, ',', '.') }}</span></p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $therapists->links() }}</div>
    @endif
</section>
@endsection
