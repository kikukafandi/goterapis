@extends('layouts.app')
@section('title', 'Toko Produk Terapi & Herbal')
@section('meta', 'Jelajahi produk herbal dan perlengkapan terapi yang tersedia di GoTerapis.')

@section('content')
<div class="mx-auto max-w-6xl px-5 pb-28 pt-5 sm:px-4 sm:pb-16 sm:pt-9">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.06em] text-daun">Toko GoTerapis</p>
            <h1 class="font-display mt-2 text-[26px] font-extrabold text-arang sm:text-[32px]">Produk untuk perawatan</h1>
            <p class="mt-2 max-w-xl text-[13px] font-medium leading-relaxed text-kabut-muda">Herbal dan perlengkapan terapi yang tersedia di katalog GoTerapis.</p>
        </div>
        <span class="hidden text-xs font-medium text-kabut-samar sm:block">{{ $products->total() }} produk</span>
    </div>

    @if ($banners->isNotEmpty())
        <section x-data="{ active: 0, total: {{ $banners->count() }} }" class="relative mt-6 overflow-hidden rounded-[24px] border border-garis bg-white">
            @foreach ($banners as $banner)
                <a x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif href="{{ $banner->cta_url ?: '#katalog-produk' }}" class="grid sm:grid-cols-[1.2fr_.8fr]">
                    <img src="{{ $banner->imageUrl() }}" alt="" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif class="h-44 w-full object-cover sm:h-64">
                    <span class="flex flex-col justify-center bg-daun-terang p-6 text-white sm:p-8">
                        <span class="text-[10px] font-bold uppercase tracking-[.06em] text-white/75">Pilihan GoTerapis</span>
                        <strong class="font-display mt-3 text-2xl font-extrabold leading-tight">{{ $banner->title }}</strong>
                        @if ($banner->subtitle)<span class="mt-3 text-[13px] font-medium leading-relaxed text-white/80">{{ $banner->subtitle }}</span>@endif
                    </span>
                </a>
            @endforeach
            @if ($banners->count() > 1)
                <div class="absolute bottom-3 right-3 flex rounded-xl bg-white p-1">
                    <button type="button" @click="active = (active - 1 + total) % total" aria-label="Sebelumnya" class="grid h-8 w-8 place-items-center text-arang">‹</button>
                    <button type="button" @click="active = (active + 1) % total" aria-label="Berikutnya" class="grid h-8 w-8 place-items-center text-arang">›</button>
                </div>
            @endif
        </section>
    @endif

    <section id="katalog-produk" class="scroll-mt-24">
        <form method="get" class="mt-6 flex gap-2 overflow-x-auto pb-1 sm:grid sm:grid-cols-[1fr_16rem_auto] sm:overflow-visible">
            <label class="flex min-w-[220px] flex-1 items-center gap-2 rounded-[14px] border border-garis bg-white px-4 py-3 sm:min-w-0">
                <x-icon name="search" class="h-4 w-4 text-kabut-samar" />
                <input name="q" value="{{ request('q') }}" placeholder="Cari produk" class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-kabut-samar">
            </label>
            <label class="flex shrink-0 items-center gap-2 rounded-[14px] border border-garis bg-white px-4 py-3">
                <select name="category" class="appearance-none bg-transparent text-xs font-semibold text-arang outline-none">
                    <option value="">Semua kategori</option>
                    @foreach (\App\Models\Product::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <button class="shrink-0 rounded-[14px] bg-daun px-5 py-3 text-xs font-bold text-white">Terapkan</button>
        </form>

        <div class="mt-5 flex items-center justify-between sm:hidden"><span class="text-xs font-medium text-kabut-muda">{{ $products->total() }} produk</span>@if (request('q') || request('category'))<a href="{{ route('products.index') }}" class="text-xs font-bold text-daun">Reset</a>@endif</div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:mt-7 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
            @forelse ($products as $product)
                <a href="{{ route('products.show', $product) }}" class="group flex min-w-0 flex-col gap-2.5 rounded-card border border-garis bg-white p-2.5 hover:border-daun-garis sm:p-3">
                    <span class="relative block h-28 overflow-hidden rounded-[14px] bg-garis-muda sm:h-44">
                        @if ($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition-transform group-hover:scale-[1.03]">@else<span class="grid h-full place-items-center text-daun"><x-icon name="leaf" class="h-9 w-9" /></span>@endif
                        @if ($product->is_promoted)<span class="absolute left-2 top-2 rounded-full bg-kunyit px-2 py-1 text-[9px] font-bold text-arang">Pilihan</span>@endif
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-1 px-1 pb-1">
                        <span class="truncate text-[10px] font-bold uppercase tracking-[.04em] text-daun">{{ $product->categoryLabel() }}</span>
                        <strong class="line-clamp-2 text-[13px] leading-snug text-arang sm:text-[15px]">{{ $product->name }}</strong>
                        <strong class="font-display mt-auto pt-1 text-sm font-extrabold text-arang sm:text-base">Rp{{ number_format($product->price, 0, ',', '.') }}</strong>
                        <span class="text-[10px] font-medium {{ $product->stock > 0 ? 'text-kabut-samar' : 'text-jahe' }}">{{ $product->stock > 0 ? 'Stok '.$product->stock : 'Stok habis' }}</span>
                    </span>
                </a>
            @empty
                <div class="col-span-full rounded-card border border-garis bg-white px-6 py-14 text-center"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-garis-muda text-kabut-samar"><x-icon name="search" class="h-7 w-7" /></span><h2 class="font-display mt-4 text-lg font-extrabold text-arang">Produk belum ditemukan</h2><p class="mt-2 text-sm text-kabut-muda">Coba kata kunci lain atau lihat semua kategori.</p><a href="{{ route('products.index') }}" class="mt-5 inline-block rounded-[14px] bg-arang px-5 py-3 text-xs font-bold text-white">Lihat semua produk</a></div>
            @endforelse
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    </section>
</div>
@endsection
