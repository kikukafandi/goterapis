@extends('layouts.admin')
@section('title', 'Banner Promosi')
@section('heading', 'Banner Promosi')
@section('content')
<header class="mb-6 flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div><h2 class="font-display text-2xl font-bold text-arang">Kampanye toko</h2><p class="mt-1.5 text-sm leading-6 text-kabut">Kelola artwork utama yang tampil di halaman toko.</p></div>
    <div class="flex flex-wrap gap-2"><a href="{{ route('products.index') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-garis px-4 text-sm font-semibold text-daun hover:bg-kertas">Lihat toko ↗</a><a href="{{ route('admin.banners.create') }}" class="inline-flex min-h-11 items-center rounded-full bg-daun px-5 text-sm font-semibold text-white hover:bg-daun-tua">Tambah banner</a></div>
</header>
<div class="space-y-4">
@forelse ($banners as $banner)
<article class="grid overflow-hidden rounded-card border border-garis bg-white lg:grid-cols-[18rem_minmax(0,1fr)_auto]">
    <img src="{{ $banner->imageUrl() }}" alt="" loading="lazy" class="aspect-[12/5] w-full object-cover lg:aspect-auto lg:h-full">
    <div class="min-w-0 p-5"><div class="flex flex-wrap items-center gap-2"><h3 class="font-display text-lg font-bold text-arang">{{ $banner->title }}</h3><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $banner->statusLabel() === 'Aktif' ? 'bg-daun-muda text-daun-tua' : 'bg-kertas text-kabut' }}">{{ $banner->statusLabel() }}</span></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-kabut">{{ $banner->subtitle ?: 'Tanpa subjudul' }}</p><p class="mt-4 text-xs leading-5 text-kabut">Urutan {{ $banner->sort_order }} · {{ $banner->starts_at?->translatedFormat('d M Y H:i') ?? 'Mulai kapan saja' }} — {{ $banner->ends_at?->translatedFormat('d M Y H:i') ?? 'Tanpa batas akhir' }}</p></div>
    <div class="flex gap-2 border-t border-garis p-4 lg:flex-col lg:justify-center lg:border-l lg:border-t-0"><a href="{{ route('admin.banners.edit', $banner) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-full border border-garis px-4 text-sm font-semibold text-arang hover:bg-kertas">Ubah</a><form method="post" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Hapus banner ini secara permanen?')" class="flex-1">@csrf @method('DELETE')<button class="min-h-10 w-full rounded-full border border-garis px-4 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button></form></div>
</article>
@empty
<div class="rounded-card border border-garis bg-white px-6 py-14 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="image" class="h-5 w-5" /></span><h3 class="mt-4 font-display text-lg font-bold text-arang">Belum ada kampanye</h3><p class="mt-1 text-sm text-kabut">Tambahkan artwork untuk ditampilkan di halaman toko.</p><a href="{{ route('admin.banners.create') }}" class="mt-5 inline-flex min-h-11 items-center rounded-full bg-daun px-5 text-sm font-semibold text-white">Tambah banner</a></div>
@endforelse
</div><div class="mt-5">{{ $banners->links() }}</div>
@endsection
