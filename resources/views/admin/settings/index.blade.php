@extends('layouts.admin')
@section('title', 'Setelan situs')
@section('heading', 'Setelan situs')
@section('subheading', 'Hero beranda & SEO')
@section('content')
@php
    $heroImage = \App\Models\Setting::imageUrl('hero_image', asset('images/hero.webp'));
    $seoImage = \App\Models\Setting::imageUrl('seo_image', asset('images/brand/logo-mark.png'));
    $nilai = fn (string $key) => old($key, \App\Models\Setting::get($key));
@endphp

<header class="mb-6 flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="font-display text-2xl font-bold text-arang">Setelan situs</h2>
        <p class="mt-1.5 text-sm leading-6 text-kabut">Judul dan gambar hero di beranda, plus meta bawaan untuk mesin pencari dan pratinjau tautan.</p>
    </div>
    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-garis px-4 text-sm font-semibold text-daun hover:bg-kertas">Lihat beranda ↗</a>
</header>

<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-2">
    @csrf @method('PUT')

    <section class="space-y-4 rounded-card border border-garis bg-white p-5 sm:p-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.16em] text-kabut">Beranda</p>
            <h3 class="mt-2 font-display text-xl font-bold text-arang">Hero</h3>
        </div>

        <label class="block">
            <span class="mb-1.5 block text-xs font-semibold text-arang">Label kecil di atas judul</span>
            <input name="hero_eyebrow" maxlength="60" value="{{ $nilai('hero_eyebrow') }}" class="isian">
            @error('hero_eyebrow') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="mb-1.5 block text-xs font-semibold text-arang">Judul <span class="text-jahe">*</span></span>
            <textarea name="hero_title" required maxlength="120" rows="2" class="isian">{{ $nilai('hero_title') }}</textarea>
            @error('hero_title') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="mb-1.5 block text-xs font-semibold text-arang">Sub judul</span>
            <textarea name="hero_subtitle" maxlength="240" rows="3" class="isian">{{ $nilai('hero_subtitle') }}</textarea>
            @error('hero_subtitle') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Tombol utama <span class="text-jahe">*</span></span>
                <input name="hero_cta_utama" required maxlength="40" value="{{ $nilai('hero_cta_utama') }}" class="isian">
                <span class="mt-1 block text-xs text-kabut">Menuju halaman pencarian terapis.</span>
                @error('hero_cta_utama') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Tombol kedua — pengunjung <span class="text-jahe">*</span></span>
                <input name="hero_cta_mitra" required maxlength="40" value="{{ $nilai('hero_cta_mitra') }}" class="isian">
                <span class="mt-1 block text-xs text-kabut">Tampil untuk tamu dan pelanggan; menuju formulir pendaftaran mitra.</span>
                @error('hero_cta_mitra') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-arang">Tombol kedua — sudah jadi terapis <span class="text-jahe">*</span></span>
                <input name="hero_cta_panel" required maxlength="40" value="{{ $nilai('hero_cta_panel') }}" class="isian">
                <span class="mt-1 block text-xs text-kabut">Menggantikan tombol di atas bila pengunjung sudah terdaftar sebagai terapis; menuju panel mitra.</span>
                @error('hero_cta_panel') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
            </label>
        </div>

        <div>
            <span class="mb-1.5 block text-xs font-semibold text-arang">Gambar hero</span>
            <img id="hero-preview" src="{{ $heroImage }}" alt="Pratinjau hero" class="aspect-[4/3] w-full rounded-xl object-cover">
            <label class="mt-3 flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-garis text-sm font-semibold text-daun hover:border-daun">
                <x-icon name="upload" />Ganti gambar
                <input type="file" name="hero_image" accept="image/*" class="sr-only"
                       onchange="if(this.files[0])document.getElementById('hero-preview').src=URL.createObjectURL(this.files[0])">
            </label>
            <p class="mt-2 text-xs text-kabut">Idealnya potret/persegi, maksimum 6 MB. Disimpan sebagai WebP lebar 1600 piksel.</p>
            @error('hero_image') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </div>
    </section>

    <section class="space-y-4 rounded-card border border-garis bg-white p-5 sm:p-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.16em] text-kabut">Mesin pencari</p>
            <h3 class="mt-2 font-display text-xl font-bold text-arang">SEO bawaan</h3>
            <p class="mt-2 text-sm leading-6 text-kabut">Dipakai di beranda dan semua halaman yang tidak punya meta sendiri.</p>
        </div>

        <label class="block">
            <span class="mb-1.5 block text-xs font-semibold text-arang">Judul halaman (title) <span class="text-jahe">*</span></span>
            <input name="seo_title" required maxlength="70" value="{{ $nilai('seo_title') }}" class="isian">
            <span class="mt-1 block text-xs text-kabut">Akhiran “— GoTerapis” ditambahkan otomatis. Idealnya di bawah 60 karakter.</span>
            @error('seo_title') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="mb-1.5 block text-xs font-semibold text-arang">Deskripsi (meta description) <span class="text-jahe">*</span></span>
            <textarea name="seo_description" required maxlength="200" rows="4" class="isian">{{ $nilai('seo_description') }}</textarea>
            <span class="mt-1 block text-xs text-kabut">Idealnya 120–160 karakter.</span>
            @error('seo_description') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </label>

        <div>
            <span class="mb-1.5 block text-xs font-semibold text-arang">Gambar bagikan (Open Graph)</span>
            <img id="seo-preview" src="{{ $seoImage }}" alt="Pratinjau gambar bagikan" class="aspect-[1.91/1] w-full rounded-xl bg-kertas object-contain">
            <label class="mt-3 flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-garis text-sm font-semibold text-daun hover:border-daun">
                <x-icon name="upload" />Ganti gambar
                <input type="file" name="seo_image" accept="image/*" class="sr-only"
                       onchange="if(this.files[0])document.getElementById('seo-preview').src=URL.createObjectURL(this.files[0])">
            </label>
            <p class="mt-2 text-xs text-kabut">Tampil saat tautan dibagikan ke WhatsApp atau media sosial. Idealnya 1200 × 630 piksel.</p>
            @error('seo_image') <span class="mt-1 block text-xs font-semibold text-jahe">{{ $message }}</span> @enderror
        </div>
    </section>

    <div class="lg:col-span-2">
        <button class="min-h-12 rounded-xl bg-daun px-6 text-sm font-bold text-white hover:bg-daun-tua">Simpan setelan</button>
    </div>
</form>
@endsection
