@extends('layouts.admin')
@section('title', 'Produk')
@section('heading', 'Produk')
@section('content')
<header class="mb-6 flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div><h2 class="font-display text-2xl font-bold text-arang">Katalog toko</h2><p class="mt-1.5 text-sm leading-6 text-kabut">Kelola informasi, harga, stok, dan urutan pilihan produk.</p></div>
    <div class="flex flex-wrap gap-2"><a href="{{ route('products.index') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-garis px-4 text-sm font-semibold text-daun hover:bg-kertas">Lihat toko ↗</a><a href="{{ route('admin.products.create') }}" class="inline-flex min-h-11 items-center rounded-full bg-daun px-5 text-sm font-semibold text-white hover:bg-daun-tua">Tambah produk</a></div>
</header>
<div class="overflow-hidden rounded-card border border-garis bg-white">
    @forelse ($products as $product)
        <article class="flex flex-col gap-3 border-b border-garis px-4 py-4 last:border-0 sm:flex-row sm:items-center sm:px-5">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                @if ($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="" loading="lazy" class="h-14 w-14 shrink-0 rounded-xl object-cover">@else<span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-daun-muda text-daun"><x-icon name="leaf" /></span>@endif
                <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="truncate font-semibold text-arang">{{ $product->name }}</p>@if($product->is_promoted)<span class="rounded-full bg-kunyit-muda px-2 py-1 text-[10px] font-bold text-arang">Pilihan GoTerapis</span>@endif</div><div class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-xs text-kabut"><span>Rp{{ number_format($product->price, 0, ',', '.') }}</span><span>·</span><span>Stok {{ $product->stock }}</span><span class="rounded-full {{ $product->status === 'draft' ? 'bg-kunyit-muda text-arang' : 'bg-daun-muda text-daun-tua' }} px-2 py-0.5 font-semibold">{{ $product->status === 'draft' ? 'Draf' : 'Terbit' }}</span></div></div>
            </div>
            <div class="flex w-full gap-2 sm:w-auto"><a href="{{ route('admin.products.edit', $product) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-full border border-garis px-4 text-sm font-semibold text-arang hover:bg-kertas sm:flex-none">Ubah</a><form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini secara permanen?')" class="flex-1 sm:flex-none">@csrf @method('DELETE')<button class="min-h-10 w-full rounded-full border border-garis px-4 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button></form></div>
        </article>
    @empty
        <div class="px-6 py-14 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="leaf" class="h-5 w-5" /></span><h3 class="mt-4 font-display text-lg font-bold text-arang">Belum ada produk</h3><p class="mt-1 text-sm text-kabut">Tambahkan produk pertama ke katalog GoTerapis.</p><a href="{{ route('admin.products.create') }}" class="mt-5 inline-flex min-h-11 items-center rounded-full bg-daun px-5 text-sm font-semibold text-white">Tambah produk</a></div>
    @endforelse
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
