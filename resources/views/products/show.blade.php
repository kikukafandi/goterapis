@extends('layouts.app')
@section('title', $product->name)
@section('meta', $product->short_description ?? 'Lihat detail, harga, dan ketersediaan produk di GoTerapis.')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-8 sm:py-14">
    <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center gap-2 font-semibold text-daun hover:underline"><x-icon name="arrow-left" /> Kembali ke toko</a>
    <div class="mt-5 grid gap-8 lg:grid-cols-2 lg:gap-14">
        <div class="relative overflow-hidden rounded-card border border-garis bg-kertas">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
                <span class="grid aspect-square place-items-center text-daun"><x-icon name="leaf" class="h-24 w-24" /></span>
            @endif
        </div>
        <div class="lg:py-4">
            <p class="font-semibold text-daun">{{ $product->categoryLabel() }}</p>
            <h1 class="mt-2 font-display text-4xl font-bold leading-tight text-arang sm:text-5xl">{{ $product->name }}</h1>
            @if ($product->short_description)<p class="mt-4 text-lg leading-relaxed text-kabut">{{ $product->short_description }}</p>@endif
            <p class="mt-7 text-3xl font-bold text-arang">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="mt-2 text-lg font-semibold {{ $product->stock > 0 ? 'text-daun' : 'text-jahe' }}">{{ $product->stock > 0 ? 'Tersedia · '.$product->stock.' stok' : 'Stok sedang habis' }}</p>
            <div class="mt-8 border-l-4 border-kunyit bg-daun-muda p-5">
                <p class="font-display text-lg font-bold text-arang">Informasi ketersediaan</p>
                <p class="mt-2 text-sm leading-6 text-kabut">Pembelian melalui GoTerapis belum tersedia. Halaman ini menyajikan informasi produk dan stok tanpa transaksi daring.</p>
            </div>
            <dl class="mt-8 grid gap-4 border-y border-garis py-6 sm:grid-cols-2">
                @if ($product->origin)<div><dt class="text-sm font-semibold text-kabut">Asal</dt><dd class="mt-1 text-arang">{{ $product->origin }}</dd></div>@endif
                @if ($product->weight_grams)<div><dt class="text-sm font-semibold text-kabut">Berat</dt><dd class="mt-1 text-arang">{{ number_format($product->weight_grams, 0, ',', '.') }} gram</dd></div>@endif
            </dl>
        </div>
    </div>
    @if ($product->description || $product->storage_instructions)
        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @if ($product->description)<article class="rounded-card border border-garis bg-white p-6 lg:col-span-2"><h2 class="font-display text-2xl font-bold text-arang">Tentang produk</h2><div class="mt-4 whitespace-pre-line leading-8 text-arang/80">{{ $product->description }}</div></article>@endif
            @if ($product->storage_instructions)<aside class="rounded-card bg-daun-tua p-6 text-white"><h2 class="font-display text-xl font-bold">Cara penyimpanan</h2><p class="mt-3 leading-7 text-white/85">{{ $product->storage_instructions }}</p></aside>@endif
        </div>
    @endif
</section>
@endsection
