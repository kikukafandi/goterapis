@extends('layouts.app')
@section('title', $product->name)
@section('meta', $product->short_description ?? 'Lihat detail, harga, dan ketersediaan produk di GoTerapis.')
@section('canonical', route('products.show', $product))
@section('image', $product->imageUrl() ? url($product->imageUrl()) : asset('images/brand/logo-mark.png'))
@section('type', 'product')
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->name, 'description' => $product->short_description, 'image' => $product->imageUrl() ? url($product->imageUrl()) : asset('images/brand/logo-mark.png'), 'url' => route('products.show', $product), 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IDR', 'price' => $product->price, 'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => route('products.show', $product)]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="mx-auto max-w-6xl pb-32 sm:px-4 sm:pb-16 sm:pt-8">
    <a href="{{ route('products.index') }}" aria-label="Kembali ke toko" class="absolute left-5 top-[86px] z-10 grid h-[38px] w-[38px] place-items-center rounded-full border border-garis bg-white/95 text-arang sm:static sm:mb-5 sm:inline-flex sm:w-auto sm:gap-2 sm:rounded-xl sm:px-4 sm:text-xs sm:font-bold"><x-icon name="arrow-left" class="h-4 w-4" /><span class="hidden sm:inline">Kembali ke toko</span></a>

    <div class="grid items-start sm:gap-8 lg:grid-cols-[1fr_.9fr] lg:gap-14">
        <section class="overflow-hidden bg-garis-muda sm:rounded-[28px] sm:border sm:border-garis">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-[280px] w-full object-cover sm:aspect-square sm:h-auto">
            @else
                <span class="grid h-[280px] place-items-center text-daun sm:aspect-square sm:h-auto"><x-icon name="leaf" class="h-20 w-20" /></span>
            @endif
        </section>

        <section class="px-5 py-5 sm:px-0 sm:py-4">
            <p class="text-[10px] font-bold uppercase tracking-[.06em] text-daun">{{ $product->categoryLabel() }}</p>
            <h1 class="font-display mt-2 text-[25px] font-extrabold leading-tight text-arang sm:text-[36px]">{{ $product->name }}</h1>
            <p class="font-display mt-3 text-[24px] font-extrabold text-daun">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
            @if ($product->short_description)<p class="mt-5 text-[13px] font-medium leading-relaxed text-kabut sm:text-[15px]">{{ $product->short_description }}</p>@endif

            <dl class="mt-6 space-y-3 rounded-[18px] border border-garis bg-white p-4">
                <div class="flex justify-between gap-4"><dt class="text-[13px] font-medium text-kabut-muda">Ketersediaan</dt><dd class="text-right text-[13px] font-semibold {{ $product->stock > 0 ? 'text-arang' : 'text-jahe' }}">{{ $product->stock > 0 ? $product->stock.' stok' : 'Stok habis' }}</dd></div>
                @if ($product->origin)<div class="flex justify-between gap-4"><dt class="text-[13px] font-medium text-kabut-muda">Asal</dt><dd class="text-right text-[13px] font-semibold text-arang">{{ $product->origin }}</dd></div>@endif
                @if ($product->weight_grams)<div class="flex justify-between gap-4"><dt class="text-[13px] font-medium text-kabut-muda">Berat</dt><dd class="text-right text-[13px] font-semibold text-arang">{{ number_format($product->weight_grams, 0, ',', '.') }} gram</dd></div>@endif
            </dl>

            <div class="mt-5 rounded-[18px] bg-kunyit-muda p-4 text-[12px] font-medium leading-relaxed text-kunyit-tua">Pembelian daring belum tersedia. Halaman ini menampilkan informasi produk dan stok yang dikelola GoTerapis.</div>
        </section>
    </div>

    @if ($product->description || $product->storage_instructions)
        <div class="grid gap-4 px-5 sm:mt-8 sm:px-0 lg:grid-cols-[1.4fr_.6fr]">
            @if ($product->description)<article class="rounded-card border border-garis bg-white p-5 sm:p-6"><h2 class="font-display text-lg font-extrabold text-arang">Tentang produk</h2><div class="mt-3 whitespace-pre-line text-[13px] leading-relaxed text-kabut sm:text-sm">{{ $product->description }}</div></article>@endif
            @if ($product->storage_instructions)<aside class="rounded-card bg-malam p-5 text-white sm:p-6"><h2 class="font-display text-lg font-extrabold">Cara penyimpanan</h2><p class="mt-3 text-[13px] leading-relaxed text-white/60">{{ $product->storage_instructions }}</p></aside>@endif
        </div>
    @endif
</div>
@endsection
