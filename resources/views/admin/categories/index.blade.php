@extends('layouts.admin')
@section('title', 'Kategori')
@section('heading', 'Kategori')
@section('content')
<header class="mb-6 flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="font-display text-2xl font-bold text-arang">Kategori layanan</h2>
        <p class="mt-1.5 text-sm leading-6 text-kabut">Kategori yang tampil di beranda dan filter pencarian. Urutan kecil tampil lebih dulu.</p>
    </div>
    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-garis px-4 text-sm font-semibold text-daun hover:bg-kertas">Lihat beranda ↗</a>
</header>

@error('hapus')
    <p class="mb-5 rounded-card border border-jahe-garis bg-jahe-muda px-4 py-3 text-sm font-semibold text-jahe">{{ $message }}</p>
@enderror

<form method="post" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data"
      class="mb-6 grid gap-3 rounded-card border border-garis bg-white p-5 sm:grid-cols-[minmax(0,1fr)_7rem_12rem_auto] sm:items-end">
    @csrf
    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-arang">Nama kategori</span>
        <input name="name" required maxlength="60" value="{{ old('name') }}" placeholder="Misal: Refleksi Kaki" class="isian">
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-arang">Urutan</span>
        <input name="position" type="number" min="0" max="999" value="{{ old('position', $categories->count()) }}" class="isian">
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-arang">Ikon (opsional)</span>
        <input name="icon" type="file" accept="image/*" class="isian file:mr-3 file:rounded-full file:border-0 file:bg-kertas file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-arang">
    </label>
    <button class="min-h-12 rounded-xl bg-daun px-5 text-sm font-bold text-white hover:bg-daun-tua">Tambah</button>
    @error('name') <p class="text-xs font-semibold text-jahe sm:col-span-4">{{ $message }}</p> @enderror
    @error('icon') <p class="text-xs font-semibold text-jahe sm:col-span-4">{{ $message }}</p> @enderror
</form>

<div class="space-y-3">
    @forelse ($categories as $category)
        <form method="post" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data"
              class="grid gap-3 rounded-card border border-garis bg-white p-4 sm:grid-cols-[auto_minmax(0,1fr)_6rem_11rem_auto] sm:items-end">
            @csrf
            <span class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-garis-muda">
                <x-cat-icon :slug="$category->slug" :src="$category->iconUrl()" class="h-full w-full" />
            </span>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Nama <span class="font-normal text-kabut-samar">· {{ $category->slug }} · {{ $category->services_count }} layanan</span></span>
                <input name="name" required maxlength="60" value="{{ $category->name }}" class="isian">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Urutan</span>
                <input name="position" type="number" min="0" max="999" value="{{ $category->position }}" class="isian">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Ganti ikon</span>
                <input name="icon" type="file" accept="image/*" class="isian file:mr-3 file:rounded-full file:border-0 file:bg-kertas file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-arang">
            </label>
            <div class="flex gap-2">
                <button class="min-h-12 rounded-xl border-[1.5px] border-garis px-4 text-sm font-bold text-arang hover:bg-kertas">Simpan</button>
            </div>
        </form>
        <form method="post" action="{{ route('admin.categories.destroy', $category) }}"
              onsubmit="return confirm('Hapus kategori {{ $category->name }}?')" class="-mt-1 pl-1">
            @csrf @method('DELETE')
            <button class="text-xs font-semibold text-jahe hover:underline">Hapus kategori</button>
        </form>
    @empty
        <div class="rounded-card border border-garis bg-white px-6 py-14 text-center">
            <h3 class="font-display text-lg font-bold text-arang">Belum ada kategori</h3>
            <p class="mt-1 text-sm text-kabut">Tambahkan kategori pertama lewat formulir di atas.</p>
        </div>
    @endforelse
</div>
@endsection
