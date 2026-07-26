@extends('layouts.admin')
@section('title', 'Produk')
@section('heading', 'Produk')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h2 class="font-display text-2xl font-bold text-arang">Katalog toko</h2><p class="mt-1 text-sm text-kabut">Kelola informasi, harga, stok, dan urutan pilihan produk.</p></div><div class="flex gap-3"><a href="{{ route('products.index') }}" target="_blank" class="border-b border-daun pb-1 text-sm font-semibold text-daun">Lihat toko</a><a href="{{ route('admin.products.create') }}" class="rounded-xl bg-daun px-4 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">Tambah produk</a></div></div>
<div class="overflow-hidden rounded-card border border-garis bg-white">
@forelse ($products as $product)
<div class="flex flex-wrap items-center gap-3 border-b border-garis px-4 py-3 last:border-0">
@if ($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="" class="h-14 w-14 rounded-xl object-cover">@else<span class="grid h-14 w-14 place-items-center rounded-xl bg-daun-muda text-daun"><x-icon name="leaf" /></span>@endif
<div class="min-w-0 flex-1"><p class="truncate font-semibold text-arang">{{ $product->name }} @if($product->is_promoted)<span class="ml-2 text-xs font-semibold text-daun">Pilihan GoTerapis</span>@endif</p><p class="text-xs text-kabut">Rp{{ number_format($product->price, 0, ',', '.') }} · Stok {{ $product->stock }} · <span class="{{ $product->status === 'draft' ? 'text-jahe' : 'text-daun' }}">{{ $product->status === 'draft' ? 'Draf' : 'Terbit' }}</span></p></div>
<a href="{{ route('admin.products.edit', $product) }}" class="rounded-full border border-garis px-3 py-2 text-sm font-semibold text-arang hover:bg-kertas">Ubah</a>
<form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini secara permanen?')">@csrf @method('DELETE')<button class="rounded-full border border-garis px-3 py-2 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button></form>
</div>
@empty <div class="p-10 text-center"><x-icon name="leaf" class="mx-auto h-10 w-10 text-daun" /><p class="mt-3 text-kabut">Belum ada produk. Tambahkan produk pertama ke katalog.</p></div> @endforelse
</div><div class="mt-4">{{ $products->links() }}</div>
@endsection
