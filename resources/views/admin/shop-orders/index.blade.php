@extends('layouts.admin')
@section('title', 'Pesanan Toko')
@section('heading', 'Pesanan Toko')
@section('subheading', 'Atur ongkir, proses, dan pengiriman produk')
@section('content')
@php($labels = ['waiting_shipping'=>'Menunggu ongkir','pending_payment'=>'Menunggu pembayaran','paid'=>'Lunas','processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Dibatalkan'])
<div class="overflow-hidden rounded-card border border-garis bg-white">@forelse($orders as $order)<a href="{{ route('admin.shop-orders.show', $order) }}" class="flex flex-col gap-2 border-b border-garis p-5 last:border-0 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold text-arang">{{ $order->code }}</p><p class="mt-1 text-xs text-kabut">{{ $order->user->name }} · {{ $order->city }}</p></div><div class="sm:text-right"><span class="rounded-full bg-daun-muda px-3 py-1 text-xs font-bold text-daun-tua">{{ $labels[$order->status] }}</span><p class="mt-2 text-sm font-bold text-arang">{{ $order->total === null ? 'Rp'.number_format($order->subtotal, 0, ',', '.').' + ongkir' : 'Rp'.number_format($order->total, 0, ',', '.') }}</p></div></a>@empty<div class="p-12 text-center text-sm text-kabut">Belum ada pesanan toko.</div>@endforelse</div><div class="mt-4">{{ $orders->links() }}</div>
@endsection
