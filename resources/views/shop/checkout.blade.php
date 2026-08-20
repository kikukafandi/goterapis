@extends('layouts.app')
@section('title', 'Checkout Toko')
@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6"><h1 class="font-display text-2xl font-extrabold text-arang">Alamat pengiriman</h1><p class="mt-2 text-sm text-kabut">Ongkos kirim ditetapkan admin setelah alamat diperiksa.</p>
<form method="post" action="{{ route('shop.orders.store') }}" class="mt-5 space-y-4 rounded-card border border-garis bg-white p-5">@csrf
@foreach ([['recipient_name','Nama penerima'],['phone','Nomor telepon'],['city','Kota/Kabupaten'],['postal_code','Kode pos']] as [$name,$label])<label class="block"><span class="mb-1.5 block text-sm font-bold text-arang">{{ $label }}</span><input name="{{ $name }}" value="{{ old($name, $name === 'recipient_name' ? auth()->user()->name : ($name === 'phone' ? auth()->user()->phone : '')) }}" {{ $name !== 'postal_code' ? 'required' : '' }} class="w-full rounded-xl border border-garis bg-kertas-isian px-4 py-3 text-sm"></label>@endforeach
<label class="block"><span class="mb-1.5 block text-sm font-bold text-arang">Alamat lengkap</span><textarea name="address" required rows="4" class="w-full rounded-xl border border-garis bg-kertas-isian px-4 py-3 text-sm">{{ old('address') }}</textarea></label>
<div class="border-t border-garis-muda pt-4"><p class="text-sm text-kabut">{{ $items->sum('quantity') }} barang · Subtotal <strong class="text-arang">Rp{{ number_format($items->sum(fn ($item) => $item->product->price * $item->quantity), 0, ',', '.') }}</strong></p><button class="mt-4 w-full rounded-full bg-daun px-5 py-3 text-sm font-bold text-white">Buat pesanan</button></div></form></div>
@endsection
