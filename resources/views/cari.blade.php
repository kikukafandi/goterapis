@extends('layouts.app')
@section('title', 'Cari Terapis Sesuai Kebutuhan')
@section('robots', 'noindex, follow')

@section('content')
<div x-data="{ filters: false, lokasi: false, pesanLokasi: '' }" class="mx-auto max-w-6xl pb-28 pt-2 sm:px-4 sm:pt-8 lg:pb-12">
    <form action="{{ route('cari') }}" method="get" class="flex items-center gap-2 px-5 sm:rounded-card sm:border sm:border-garis sm:bg-white sm:p-3">
        <label class="flex min-w-0 flex-1 items-center gap-2.5 rounded-2xl border border-garis bg-white px-4 py-[13px] sm:bg-kertas">
            <x-icon name="search" class="h-[18px] w-[18px] shrink-0 text-kabut-samar" />
            <input name="q" value="{{ $q }}" type="search" placeholder="Layanan atau nama terapis" class="min-w-0 flex-1 bg-transparent text-sm font-medium text-arang outline-none placeholder:text-kabut-samar">
        </label>
        <label class="flex shrink-0 items-center gap-1.5 rounded-2xl border border-garis bg-white px-3.5 py-[13px] sm:w-52 sm:px-4">
            <x-icon name="pin" class="hidden h-4 w-4 text-daun sm:block" />
            <select name="kota" class="max-w-[86px] appearance-none bg-transparent text-xs font-semibold text-arang outline-none sm:max-w-none sm:flex-1">
                <option value="">Semua kota</option>
                @foreach ($cities as $city)
                    <option value="{{ $city }}" @selected($kota === $city)>{{ $city }}</option>
                @endforeach
            </select>
        </label>
        @if ($kategori)
            <input type="hidden" name="kategori" value="{{ $kategori }}">
        @endif
        @foreach (['model' => $model, 'gender' => $gender, 'lat' => $latitude, 'lng' => $longitude, 'radius' => $latitude !== null ? $radius : null] as $key => $value)
            @if ($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <button class="hidden rounded-xl bg-daun px-6 py-3 text-sm font-bold text-white sm:block">Cari</button>
    </form>
    <form action="{{ route('cari') }}" method="get" class="mt-3 flex flex-wrap items-center gap-2 px-5 sm:px-0" x-ref="locationForm">
        @foreach (['q' => $q, 'kategori' => $kategori, 'kota' => $kota, 'model' => $model, 'gender' => $gender] as $key => $value)
            @if ($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
        @endforeach
        <input type="hidden" name="lat" value="{{ $latitude }}" x-ref="lat"><input type="hidden" name="lng" value="{{ $longitude }}" x-ref="lng"><input type="hidden" name="sort" value="terdekat">
        <label class="text-xs font-semibold text-arang">Radius <select name="radius" class="rounded-xl border border-garis bg-white px-3 py-2 text-xs">@foreach ([5, 10, 25, 50, 100] as $value)<option value="{{ $value }}" @selected($radius === $value)>{{ $value }} km</option>@endforeach</select></label>
        <button type="button" @click="lokasi = true; pesanLokasi = ''; navigator.geolocation.getCurrentPosition((pos) => { $refs.lat.value = pos.coords.latitude; $refs.lng.value = pos.coords.longitude; $refs.locationForm.submit() }, () => { lokasi = false; pesanLokasi = 'Lokasi tidak dapat diambil. Gunakan filter kota.' }, { enableHighAccuracy: true, timeout: 10000 })" class="rounded-xl border border-daun px-4 py-2.5 text-xs font-bold text-daun" :disabled="lokasi"><span x-text="lokasi ? 'Mengambil lokasi…' : 'Cari di dekat saya'"></span></button>
        @if ($latitude !== null)<a href="{{ request()->fullUrlWithQuery(['lat' => null, 'lng' => null, 'radius' => null, 'sort' => null, 'page' => null]) }}" class="text-xs font-semibold text-kabut">Kembali ke kota</a>@endif
        <span class="w-full text-xs text-jahe" role="status" aria-live="polite" x-text="pesanLokasi"></span>
    </form>

    <div class="mt-4 flex gap-2 overflow-x-auto px-5 pb-1 sm:px-0 lg:hidden">
        <button type="button" @click="filters = true" class="flex shrink-0 items-center gap-2 rounded-full border border-garis bg-white px-4 py-2.5 text-xs font-semibold text-arang"><x-icon name="menu" class="h-4 w-4" /> Filter</button>
        <a href="{{ request()->fullUrlWithQuery(['kategori' => null, 'page' => null]) }}" class="shrink-0 rounded-full border px-4 py-2.5 text-xs font-semibold {{ ! $kategori ? 'border-daun bg-daun-muda text-daun-tua' : 'border-garis bg-white text-kabut' }}">Semua</a>
        @foreach ($categories as $slug => $label)
            <a href="{{ request()->fullUrlWithQuery(['kategori' => $slug, 'page' => null]) }}" class="shrink-0 rounded-full border px-4 py-2.5 text-xs font-semibold {{ $kategori === $slug ? 'border-daun bg-daun-muda text-daun-tua' : 'border-garis bg-white text-kabut' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="mt-4 grid items-start gap-7 lg:grid-cols-[264px_minmax(0,1fr)]">
        <aside class="hidden rounded-[22px] border border-garis bg-white p-5 lg:sticky lg:top-24 lg:block">
            @include('partials.search-filters')
        </aside>

        <main class="min-w-0 px-5 sm:px-0">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-medium text-kabut-muda">
                    {{ $therapists->total() }} terapis ditemukan
                    @if ($q)
                        untuk “{{ $q }}”
                    @endif
                </p>
                <form action="{{ route('cari') }}" method="get" class="shrink-0">
                    @foreach (['q' => $q, 'kategori' => $kategori, 'kota' => $kota, 'model' => $model, 'gender' => $gender, 'lat' => $latitude, 'lng' => $longitude, 'radius' => $latitude !== null ? $radius : null] as $key => $value)
                        @if ($value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="flex items-center gap-1.5">
                        <select name="sort" onchange="this.form.submit()" aria-label="Urutkan hasil" class="appearance-none bg-transparent text-xs font-bold text-arang outline-none">
                            <option value="rekomendasi" @selected($sort === 'rekomendasi')>Rekomendasi</option>
                            <option value="rating" @selected($sort === 'rating')>Rating tertinggi</option>
                            <option value="termurah" @selected($sort === 'termurah')>Harga termurah</option>
                            <option value="terlaris" @selected($sort === 'terlaris')>Terlaris</option>
                            @if ($latitude !== null)<option value="terdekat" @selected($sort === 'terdekat')>Jarak terdekat</option>@endif
                        </select>
                    </label>
                </form>
            </div>

            @if ($therapists->isEmpty())
                <div class="px-4 py-14 text-center sm:mt-5 sm:rounded-card sm:border sm:border-garis sm:bg-white">
                    <span class="mx-auto grid h-[92px] w-[92px] place-items-center rounded-full bg-garis-muda"><x-icon name="search" class="h-8 w-8 text-kabut-samar" /></span>
                    <h1 class="mt-4 font-display text-[17px] font-extrabold text-arang">Belum ada yang cocok</h1>
                    <p class="mx-auto mt-2 max-w-sm text-[13px] font-medium leading-relaxed text-kabut-muda">Coba ganti kata kunci, longgarkan filter, atau perluas kota pencarian.</p>
                    <a href="{{ route('cari') }}" class="mt-5 inline-block rounded-[14px] bg-arang px-5 py-3.5 text-[13px] font-bold text-white">Hapus semua filter</a>
                </div>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3 lg:gap-4">
                    @foreach ($therapists as $therapist)
                        <a href="{{ route('terapis.show', $therapist) }}" class="group flex min-w-0 flex-col rounded-card border border-garis bg-white p-2.5 hover:border-daun-garis sm:p-3 lg:flex-row lg:gap-4">
                            <span class="relative block h-28 w-full shrink-0 overflow-hidden rounded-[14px] bg-daun-muda sm:h-44 lg:h-28 lg:w-28 lg:rounded-2xl">
                                @if ($therapist->user->avatarUrl())
                                    <img src="{{ $therapist->user->avatarUrl() }}" alt="{{ $therapist->user->name }}" loading="lazy" class="h-full w-full object-cover transition-transform group-hover:scale-[1.03]">
                                @else
                                    <span class="grid h-full place-items-center text-2xl font-extrabold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
                                @endif
                                <span class="absolute left-2 top-2 h-3.5 w-3.5 rounded-full border-2 border-white bg-daun-terang"></span>
                            </span>
                            <span class="mt-2.5 flex min-w-0 flex-1 flex-col gap-1 px-1 pb-1 lg:mt-0 lg:px-0">
                                <strong class="truncate text-[13px] text-arang sm:text-[15px]">{{ $therapist->user->name }}</strong>
                                <span class="truncate text-[10px] font-medium text-kabut-muda sm:text-xs">{{ $therapist->services->pluck('name')->take(2)->join(' · ') }}</span>
                                <span class="flex min-w-0 items-center gap-1 text-[10px] sm:text-xs"><span class="font-bold text-kunyit">★</span><strong class="text-arang">{{ number_format($therapist->rating_avg, 1, ',', '') }}</strong><span class="truncate text-kabut-samar">({{ $therapist->reviews_count }}) · {{ isset($therapist->distance_km) ? number_format($therapist->distance_km, 1, ',', '.').' km' : $therapist->city }}</span></span>
                                <span class="mt-auto flex flex-col pt-1 text-[9px] text-kabut-samar sm:block sm:text-[11px]">mulai <strong class="font-display block text-[13px] font-extrabold text-arang sm:inline sm:text-[15px]">Rp{{ number_format($therapist->starting_price ?? 0, 0, ',', '.') }}</strong></span>
                            </span>
                        </a>
                    @endforeach
                </div>

                @if ($therapists->hasPages())
                    <nav class="mt-7 flex items-center justify-center gap-2" aria-label="Navigasi halaman">
                        @if ($therapists->onFirstPage())
                            <span class="grid h-[34px] w-[34px] place-items-center rounded-[11px] border border-garis bg-white text-kabut-samar">‹</span>
                        @else
                            <a href="{{ $therapists->previousPageUrl() }}" class="grid h-[34px] w-[34px] place-items-center rounded-[11px] border border-garis bg-white font-bold text-arang">‹</a>
                        @endif
                        <span class="grid h-[34px] min-w-[34px] place-items-center rounded-[11px] bg-arang px-2 text-xs font-bold text-white">{{ $therapists->currentPage() }}</span>
                        <span class="text-xs text-kabut-samar">dari {{ $therapists->lastPage() }}</span>
                        @if ($therapists->hasMorePages())
                            <a href="{{ $therapists->nextPageUrl() }}" class="grid h-[34px] w-[34px] place-items-center rounded-[11px] border border-garis bg-white font-bold text-arang">›</a>
                        @else
                            <span class="grid h-[34px] w-[34px] place-items-center rounded-[11px] border border-garis bg-white text-kabut-samar">›</span>
                        @endif
                    </nav>
                @endif
            @endif
        </main>
    </div>

    <div x-cloak x-show="filters" class="fixed inset-0 z-[60] lg:hidden">
        <button type="button" @click="filters = false" aria-label="Tutup filter" class="absolute inset-0 bg-arang/40"></button>
        <aside class="absolute inset-x-0 bottom-0 max-h-[82dvh] overflow-y-auto rounded-t-[28px] bg-white px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-5" x-transition>
            <div class="mx-auto mb-5 h-1 w-10 rounded-full bg-garis"></div>
            @include('partials.search-filters')
            <button type="button" @click="filters = false" class="mt-6 w-full rounded-[14px] bg-daun py-4 text-sm font-bold text-white">Lihat hasil</button>
        </aside>
    </div>
</div>
@endsection
