@extends('layouts.app')
@section('title', 'Keranjang')
@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6">
    <div class="mb-5 flex items-center justify-between"><h1 class="font-display text-2xl font-extrabold text-arang">Keranjang</h1><a href="{{ route('products.index') }}" class="text-sm font-bold text-daun">Lanjut belanja</a></div>
    <div class="space-y-3">
        @forelse ($items as $item)
            <article class="rounded-card border border-garis bg-white p-4 sm:flex sm:items-center sm:gap-4">
                <div class="min-w-0 flex-1"><h2 class="font-bold text-arang">{{ $item->product->name }}</h2><p class="mt-1 text-sm text-kabut">Rp{{ number_format($item->product->price, 0, ',', '.') }} · Stok {{ $item->product->stock }}</p></div>
                <div class="mt-3 flex items-center gap-2 sm:mt-0"><form method="post" action="{{ route('shop.cart.update', $item) }}" class="flex gap-2">@csrf @method('PATCH')<input type="number" name="quantity" min="1" max="99" value="{{ $item->quantity }}" class="w-20 rounded-xl border border-garis bg-kertas-isian px-3 py-2 text-sm"><button class="rounded-full border border-garis px-4 text-sm font-bold text-arang">Ubah</button></form><form method="post" action="{{ route('shop.cart.destroy', $item) }}">@csrf @method('DELETE')<button class="rounded-full px-3 py-2 text-sm font-bold text-jahe">Hapus</button></form></div>
            </article>
        @empty
            <div class="rounded-card border border-garis bg-white p-10 text-center"><h2 class="font-display text-lg font-bold text-arang">Keranjang masih kosong</h2><a href="{{ route('products.index') }}" class="mt-4 inline-flex rounded-full bg-daun px-5 py-3 text-sm font-bold text-white">Lihat produk</a></div>
        @endforelse
    </div>
    @if ($items->isNotEmpty())<div class="mt-5 flex items-center justify-between rounded-card bg-malam p-5 text-white"><div><p class="text-xs text-white/60">Subtotal</p><p class="font-display text-xl font-bold">Rp{{ number_format($items->sum(fn ($item) => $item->product->price * $item->quantity), 0, ',', '.') }}</p></div><a href="{{ route('shop.checkout') }}" class="rounded-full bg-daun px-5 py-3 text-sm font-bold text-white">Checkout</a></div>@endif
</div>
@endsection
