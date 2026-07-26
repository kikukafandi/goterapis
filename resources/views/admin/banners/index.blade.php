@extends('layouts.admin')
@section('title', 'Banner Promosi')
@section('heading', 'Banner Promosi')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div><h2 class="font-display text-2xl font-bold text-arang">Kampanye toko</h2><p class="mt-1 text-sm text-kabut">Kelola artwork utama yang tampil di halaman toko.</p></div>
    <div class="flex gap-3"><a href="{{ route('products.index') }}" target="_blank" class="border-b border-daun pb-1 text-sm font-semibold text-daun">Lihat toko</a><a href="{{ route('admin.banners.create') }}" class="rounded-xl bg-daun px-4 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">Tambah banner</a></div>
</div>
<div class="space-y-4">
@forelse ($banners as $banner)
<article class="grid overflow-hidden rounded-card border border-garis bg-white sm:grid-cols-[18rem_1fr_auto]">
    <img src="{{ $banner->imageUrl() }}" alt="" class="aspect-[12/5] h-full w-full object-cover sm:aspect-auto">
    <div class="min-w-0 p-5"><div class="flex flex-wrap items-center gap-x-4 gap-y-1"><h2 class="font-display text-lg font-bold text-arang">{{ $banner->title }}</h2><span class="text-sm font-semibold {{ $banner->statusLabel() === 'Aktif' ? 'text-daun' : 'text-kabut' }}">{{ $banner->statusLabel() }}</span></div><p class="mt-2 line-clamp-2 text-sm text-kabut">{{ $banner->subtitle ?: 'Tanpa subjudul' }}</p><p class="mt-4 text-xs text-kabut">Urutan {{ $banner->sort_order }} · {{ $banner->starts_at?->format('d M Y H:i') ?? 'Mulai kapan saja' }} — {{ $banner->ends_at?->format('d M Y H:i') ?? 'Tanpa batas akhir' }}</p></div>
    <div class="flex items-center gap-2 border-t border-garis p-4 sm:flex-col sm:justify-center sm:border-l sm:border-t-0"><a href="{{ route('admin.banners.edit', $banner) }}" class="w-full rounded-lg border border-garis px-4 py-2 text-center text-sm font-semibold text-arang hover:bg-kertas">Ubah</a><form method="post" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Hapus banner ini secara permanen?')" class="w-full">@csrf @method('DELETE')<button class="w-full rounded-lg border border-garis px-4 py-2 text-sm font-semibold text-jahe hover:bg-jahe/10">Hapus</button></form></div>
</article>
@empty
<div class="rounded-card border border-garis bg-white px-6 py-14 text-center"><x-icon name="image" class="mx-auto h-10 w-10 text-daun" /><h2 class="mt-4 font-display text-xl font-bold text-arang">Belum ada kampanye</h2><p class="mt-2 text-sm text-kabut">Tambahkan artwork untuk menampilkan kampanye di halaman toko.</p><a href="{{ route('admin.banners.create') }}" class="mt-5 inline-flex rounded-xl bg-daun px-4 py-2.5 text-sm font-semibold text-white">Tambah banner</a></div>
@endforelse
</div><div class="mt-5">{{ $banners->links() }}</div>
@endsection
