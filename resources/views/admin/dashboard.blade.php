@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')
@php
$cards = [
['label' => 'Pengguna', 'value' => $stats['users'], 'icon' => 'user'],
['label' => 'Terapis', 'value' => $stats['therapists'], 'icon' => 'leaf', 'href' => route('admin.therapists')],
['label' => 'Dokumen perlu ditinjau', 'value' => $stats['pending_docs'], 'icon' => 'shield', 'hot' => $stats['pending_docs'] > 0, 'href' => route('admin.therapists')],
['label' => 'Pesanan', 'value' => $stats['orders'], 'icon' => 'clipboard'],
['label' => 'Produk', 'value' => $stats['products'], 'icon' => 'leaf', 'href' => route('admin.products.index')],
['label' => 'Banner aktif', 'value' => $stats['active_banners'], 'icon' => 'image', 'href' => route('admin.banners.index')],
];
@endphp
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">@foreach($cards as $c)@if(isset($c['href']))<a href="{{ $c['href'] }}" class="rounded-card border border-garis bg-white p-4 hover:border-daun">@else<div class="rounded-card border border-garis bg-white p-4">@endif<span class="grid h-10 w-10 place-items-center rounded-xl {{ ($c['hot'] ?? false) ? 'bg-jahe/15 text-jahe' : 'bg-daun-muda text-daun' }}"><x-icon :name="$c['icon']" class="h-5 w-5" /></span><p class="mt-3 text-2xl font-bold text-arang">{{ number_format($c['value'], 0, ',', '.') }}</p><p class="text-xs text-kabut">{{ $c['label'] }}</p>@if(isset($c['href']))</a>@else</div>@endif @endforeach</div>
<section class="mt-6 rounded-card border border-garis bg-daun-tua p-5 text-white"><p class="text-xs font-bold uppercase tracking-[.18em] text-kunyit">Toko</p><div class="mt-3 flex flex-wrap items-center justify-between gap-5"><div><h2 class="font-display text-2xl font-bold">Kelola etalase GoTerapis</h2><p class="mt-1 text-sm text-white/70">Perbarui katalog dan kampanye toko dari satu tempat.</p></div><nav class="flex flex-wrap gap-4 text-sm font-semibold"><a href="{{ route('admin.products.index') }}" class="border-b border-kunyit pb-1">Kelola Produk</a><a href="{{ route('admin.banners.index') }}" class="border-b border-kunyit pb-1">Kelola Banner</a><a href="{{ route('products.index') }}" target="_blank" class="border-b border-kunyit pb-1">Lihat Toko</a></nav></div></section>
<div class="mt-6 rounded-card border border-garis bg-white"><div class="flex items-center justify-between border-b border-garis px-5 py-4"><h2 class="font-semibold text-arang">Terapis terbaru</h2><a href="{{ route('admin.therapists') }}" class="text-sm font-semibold text-daun hover:underline">Lihat semua</a></div>@forelse($latest as $t)<a href="{{ route('admin.therapist', $t) }}" class="flex items-center gap-3 border-b border-garis px-5 py-3 last:border-0 hover:bg-kertas"><span class="grid h-10 w-10 place-items-center rounded-full bg-daun-muda font-semibold text-daun">{{ mb_substr($t->user->name, 0, 1) }}</span><div class="min-w-0 flex-1"><p class="truncate font-semibold text-arang">{{ $t->user->name }}</p><p class="truncate text-xs text-kabut">{{ $t->city ?? 'Wilayah belum diisi' }} · {{ \App\Models\TherapistProfile::STATUS_LABELS[$t->verification_status] }}</p></div>@if($t->pending_count)<span class="text-xs font-semibold text-jahe">{{ $t->pending_count }} dok. menunggu</span>@endif<x-icon name="arrow-right" class="h-4 w-4 text-kabut" /></a>@empty<p class="px-5 py-8 text-center text-sm text-kabut">Belum ada terapis terdaftar.</p>@endforelse</div>
@endsection
