@extends('layouts.app')

@php
    $u = 'https://images.unsplash.com/';
    $q = '?auto=format&fit=crop&q=70';

    $categories = [
        ['slug' => 'pijat', 'label' => 'Pijat', 'desc' => 'Relaksasi dan kebugaran'],
        ['slug' => 'bekam', 'label' => 'Bekam', 'desc' => 'Perawatan tradisional'],
        ['slug' => 'kretek', 'label' => 'Kretek', 'desc' => 'Peregangan tubuh'],
        ['slug' => 'refleksi', 'label' => 'Refleksi', 'desc' => 'Pijat kaki dan tangan'],
        ['slug' => 'lainnya', 'label' => 'Kerik & Totok', 'desc' => 'Pilihan perawatan lainnya'],
    ];

    // Jenjang verifikasi — elemen signature (segel-daun).
    $ladder = [
        ['Anggota Komunitas', 'Akun & info dasar lengkap'],
        ['Identitas Terverifikasi', 'KTP, foto, telepon, rekening dicek admin'],
        ['Terapis Berpengalaman', 'Sertifikat pelatihan & pengalaman kerja'],
        ['Terapis Terdaftar', 'STPT masih berlaku bila diperlukan'],
        ['Terapis Pilihan', 'Rekam jejak & ulasan terbaik'],
    ];

    $steps = [
        ['search', 'Cari & pilih', 'Temukan terapis dekatmu berdasarkan layanan, harga, dan ulasan.'],
        ['calendar', 'Atur jadwal', 'Pilih panggilan ke rumah atau datang ke tempat praktik.'],
        ['shield', 'Bayar aman', 'Dana ditahan platform, diteruskan setelah layanan selesai.'],
    ];

    $samples = $therapists->map(function ($therapist) {
        $service = $therapist->services->first();

        return [
            'profile' => $therapist,
            'nama' => $therapist->user->name,
            'kota' => $therapist->city,
            'layanan' => $service?->name ?? 'Layanan terapi',
            'cat' => $service?->category ?? 'lainnya',
            'status' => $therapist->statusLabel(),
            'rating' => number_format($therapist->rating_avg, 1),
            'ulasan' => $therapist->reviews_count,
            'harga' => (int) ($service?->pivot?->price ?? 0),
            'avatar' => $therapist->user->avatar_path,
            'model' => $therapist->serves_call ? 'Panggilan' : 'Tempat praktik',
        ];
    });
    $availableCats = $samples->pluck('cat')->unique()->values();

    // Pencarian populer (pill gaya Fiverr) & statistik social-proof.
    // key = kategori untuk filter inline di section terapis.
    $popular = [
        ['label' => 'Pijat capek', 'key' => 'pijat'],
        ['label' => 'Bekam kering', 'key' => 'bekam'],
        ['label' => 'Refleksi kaki', 'key' => 'refleksi'],
        ['label' => 'Pijat relaksasi', 'key' => 'pijat'],
        ['label' => 'Totok wajah', 'key' => 'lainnya'],
    ];
    // Belum ada traksi nyata → tampilkan janji/kepercayaan, bukan angka palsu.
    $pillars = [
        ['shield', 'Identitas diperiksa', 'Dokumen yang diajukan diperiksa admin'],
        ['wallet', 'Harga transparan', 'Tarif tampil jelas sebelum kamu pesan'],
        ['leaf', 'Bayar aman', 'Dana ditahan sampai layanan selesai'],
        ['pin', 'Terapis lokal', 'Cari yang terdekat di sekitarmu'],
    ];

    // Testimoni — wajah asli menghidupkan halaman.
    $testimonials = [
        ['nama' => 'Andi', 'kota' => 'Yogyakarta', 'foto' => '1507003211169-0a1dd7228f2d',
         'teks' => 'Pesan pijat capek jam 9 malam, terapisnya datang tepat waktu dan sopan. Bayarnya lewat aplikasi, jadi tenang.'],
        ['nama' => 'Dewi', 'kota' => 'Sleman', 'foto' => '1494790108377-be9c29b29330',
         'teks' => 'Bisa lihat sertifikat dan ulasan dulu sebelum pesan. Ibu saya sekarang rutin panggil terapis refleksi tiap minggu.'],
        ['nama' => 'Rani', 'kota' => 'Bantul', 'foto' => '1573497019940-1c28c88b4f3e',
         'teks' => 'Harganya jelas dari awal, tidak ada biaya kaget. Bisa chat langsung dengan terapisnya juga enak.'],
    ];

    function rupiah($n) { return 'Rp' . number_format($n, 0, ',', '.'); }
@endphp

@section('content')
<div x-data="{ cat: '', has: @js($availableCats), labels: @js(['pijat'=>'Pijat','bekam'=>'Bekam','kretek'=>'Kretek','refleksi'=>'Refleksi','lainnya'=>'Kerik & Totok']) }">
{{-- ============ HERO (latar foto + overlay hijau solid) ============ --}}
<section class="relative isolate overflow-hidden">
    {{-- Foto full-bleed + lapisan hijau daun solid (bukan gradasi) --}}
    <img src="{{ asset('images/hero.webp') }}"
         alt="" aria-hidden="true" loading="eager" fetchpriority="high" width="1280" height="880"
         class="absolute inset-0 -z-10 h-full w-full object-cover">
    <div class="absolute inset-0 -z-10 bg-daun/75"></div>

    <div class="mx-auto max-w-6xl px-4 py-16 sm:py-20 lg:py-28">
        <div class="max-w-2xl">
            <h1 class="mt-4 font-display text-4xl font-bold leading-[1.08] text-white sm:text-5xl lg:text-6xl">
                Temukan terapis<br>di <span class="text-kunyit">sekitarmu.</span>
            </h1>
            <p class="mt-4 max-w-lg text-base text-white/85 sm:text-lg">
                Bandingkan layanan, harga, dan ulasan terapis pijat, bekam, kretek, serta refleksi.
                Pilih jadwal yang sesuai, lalu pesan melalui GoTerapis.
            </p>

            {{-- Search hero (card putih) — versi ringkas muncul di navbar saat tergulir --}}
            <form action="/cari" method="get" class="mt-7 rounded-card bg-white p-2 shadow-lg">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <label class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2.5 focus-within:bg-kertas">
                        <x-icon name="search" class="h-5 w-5 shrink-0 text-daun" />
                        <input name="q" type="text" placeholder="Layanan, mis. pijat capek"
                               class="w-full bg-transparent text-sm outline-none placeholder:text-kabut">
                    </label>
                    <label class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2.5 focus-within:bg-kertas sm:border-l sm:border-garis">
                        <x-icon name="pin" class="h-5 w-5 shrink-0 text-daun" />
                        <select name="kota" class="w-full bg-transparent text-sm outline-none">
                            <option value="">Pilih kota</option>
                            <option>Yogyakarta</option><option>Sleman</option>
                            <option>Bantul</option><option>Jakarta</option><option>Bandung</option>
                        </select>
                    </label>
                    <button class="rounded-xl bg-daun px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">
                        Cari terapis
                    </button>
                </div>
            </form>

            {{-- Pencarian populer — filter langsung di halaman ini --}}
            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                <span class="text-white/80">Populer:</span>
                @foreach ($popular as $p)
                    <button type="button"
                            @click="cat = '{{ $p['key'] }}'; $nextTick(() => document.getElementById('daftar-terapis').scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            :class="cat === '{{ $p['key'] }}' ? 'border-white bg-white text-daun' : 'border-white/30 bg-white/10 text-white hover:bg-white/20'"
                            class="rounded-full border px-3 py-1 text-xs font-medium backdrop-blur transition-colors">
                        {{ $p['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-white/85">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-kunyit"></span> Identitas diperiksa</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-kunyit"></span> Harga transparan</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-kunyit"></span> Bayar aman</span>
            </div>
        </div>
    </div>
</section>

{{-- ============ PILAR KEPERCAYAAN (pengganti statistik) ============ --}}
<section class="border-b border-garis bg-white">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-x-4 gap-y-6 px-4 py-8 sm:grid-cols-4">
        @foreach ($pillars as [$icon, $title, $desc])
            <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-start sm:gap-3 sm:text-left">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-daun-muda text-daun">
                    <x-icon name="{{ $icon }}" class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-sm font-bold text-arang">{{ $title }}</p>
                    <p class="mt-0.5 text-xs text-kabut">{{ $desc }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ============ KATEGORI ============ --}}
<section class="mx-auto max-w-6xl px-4 pt-10 pb-4">
    <h2 class="mb-5 font-display text-xl font-bold text-arang">Jelajahi layanan</h2>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($categories as $c)
            <button type="button"
                    @click="cat = '{{ $c['slug'] }}'; $nextTick(() => document.getElementById('daftar-terapis').scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                    class="group flex flex-col rounded-2xl border bg-white p-2.5 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="cat === '{{ $c['slug'] }}' ? 'border-daun' : 'border-garis hover:border-daun'">
                <span class="block aspect-[4/3] w-full overflow-hidden rounded-xl">
                    <x-cat-icon :slug="$c['slug']" class="h-full w-full" />
                </span>
                <span class="flex items-center gap-2 px-1.5 pb-1 pt-3">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-arang">{{ $c['label'] }}</span>
                        <span class="block truncate text-xs text-kabut">{{ $c['desc'] }}</span>
                    </span>
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-daun-muda text-daun transition-colors group-hover:bg-daun group-hover:text-white">
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </span>
                </span>
            </button>
        @endforeach
    </div>
</section>

{{-- ============ TERAPIS UNGGULAN ============ --}}
<section id="daftar-terapis" class="relative overflow-hidden py-14">
    {{-- Dekorasi latar --}}
    <div class="pointer-events-none absolute -left-32 top-20 h-72 w-72 rounded-full bg-daun/5 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-32 bottom-0 h-80 w-80 rounded-full bg-kunyit/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-4">
        {{-- Header --}}
        <div class="flex items-end justify-between gap-5">
            <div class="max-w-2xl">

                <h2 class="font-display text-3xl font-bold leading-tight text-arang sm:text-4xl">
                    <span x-show="!cat">
                        Temukan terapis yang
                        <span class="text-daun">tepat untukmu</span>
                    </span>

                    <span x-show="cat" x-cloak>
                        Terapis
                        <span class="text-daun" x-text="labels[cat]"></span>
                    </span>
                </h2>

                <p class="mt-3 max-w-xl text-sm leading-6 text-kabut sm:text-base">
                    Profil pilihan berdasarkan status verifikasi, informasi layanan, dan ulasan.
                </p>
            </div>

            {{-- Reset filter --}}
            <button
                type="button"
                x-show="cat"
                x-cloak
                @click="cat = ''"
                class="hidden shrink-0 items-center gap-2 rounded-full border border-garis bg-white px-4 py-2.5 text-sm font-semibold text-arang transition-all hover:border-daun hover:text-daun sm:inline-flex"
            >
                <x-icon name="close" class="h-4 w-4" />
                Tampilkan semua
            </button>

            <a
                href="/cari"
                x-show="!cat"
                class="group hidden shrink-0 items-center gap-2 text-sm font-bold text-daun sm:inline-flex"
            >
                Lihat semua

                <span class="grid h-8 w-8 place-items-center rounded-full bg-daun/10 transition-transform group-hover:translate-x-1">
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </span>
            </a>
        </div>

        {{-- Daftar card --}}
        <div class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($samples as $t)
                <article
                    x-show="!cat || cat === '{{ $t['cat'] }}'"
                    x-transition
                    class="group relative overflow-hidden rounded-[26px] bg-white shadow-[0_8px_35px_rgba(30,55,45,0.08)] ring-1 ring-garis/80 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_18px_50px_rgba(30,55,45,0.15)] hover:ring-daun/30"
                >
                    {{-- Foto --}}
                    <div class="relative h-56 overflow-hidden">
                        <img
                            src="{{ $t['avatar'] ? (str_starts_with($t['avatar'], 'http') ? $t['avatar'] : asset('storage/'.$t['avatar'])) : 'https://ui-avatars.com/api/?name='.urlencode($t['nama']).'&background=e8f3ed&color=276749&size=700' }}"
                            alt="{{ $t['layanan'] }}"
                            loading="lazy"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        >

                        {{-- Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-arang/75 via-arang/10 to-transparent"></div>

                        {{-- Status atas --}}
                        <div class="absolute left-4 top-4">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/95 px-3 py-1.5 text-[11px] font-bold text-daun-tua shadow-sm backdrop-blur">
                                <span class="grid h-5 w-5 place-items-center rounded-full bg-daun/10">
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-3.5 w-3.5 text-daun"
                                        fill="currentColor"
                                    >
                                        <path d="M20 4C10 4 4 10 4 20c8 0 16-6 16-16Z"/>
                                    </svg>
                                </span>

                                {{ $t['status'] }}
                            </span>
                        </div>

                        {{-- Model layanan --}}
                        <div class="absolute right-4 top-4">
                            <span class="inline-flex items-center rounded-full bg-arang/70 px-3 py-1.5 text-[11px] font-semibold text-white backdrop-blur-md">
                                {{ $t['model'] }}
                            </span>
                        </div>

                        {{-- Nama di atas gambar --}}
                        <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate font-display text-xl font-bold text-white">
                                        {{ $t['nama'] }}
                                    </h3>

                                    <x-badge
                                        :status="$t['status']"
                                        size="h-5 w-5"
                                        class="shrink-0"
                                    />
                                </div>

                                <p class="mt-1 flex items-center gap-1.5 text-xs text-white/80">
                                    <x-icon name="location" class="h-3.5 w-3.5" />
                                    {{ $t['kota'] }}
                                </p>
                            </div>

                            {{-- Rating --}}
                            <div class="shrink-0 rounded-2xl bg-white px-3 py-2 text-center shadow-md">
                                <div class="flex items-center justify-center gap-1">
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4 text-kunyit"
                                        fill="currentColor"
                                    >
                                        <path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/>
                                    </svg>

                                    <span class="text-sm font-bold text-arang">
                                        {{ $t['rating'] }}
                                    </span>
                                </div>

                                <p class="mt-0.5 text-[9px] font-medium text-kabut">
                                    {{ $t['ulasan'] }} ulasan
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Isi --}}
                    <div class="p-5">
                        {{-- Layanan --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-kabut">
                                    Layanan utama
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-daun/10 px-3 py-1.5 text-xs font-bold text-daun-tua">
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="h-3.5 w-3.5 text-daun"
                                            fill="currentColor"
                                        >
                                            <path d="M20 4C10 4 4 10 4 20c8 0 16-6 16-16Z"/>
                                        </svg>

                                        {{ $t['layanan'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Badge besar --}}
                            <div class="relative shrink-0">
                                <div class="absolute inset-0 rounded-full bg-daun/15 blur-lg"></div>

                                <x-badge
                                    :status="$t['status']"
                                    size="h-14 w-14"
                                    class="relative drop-shadow-sm"
                                />
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="my-5 border-t border-dashed border-garis"></div>

                        {{-- Harga dan CTA --}}
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-medium text-kabut">
                                    Mulai dari
                                </p>

                                <p class="mt-0.5 font-display text-lg font-bold text-arang">
                                    {{ rupiah($t['harga']) }}
                                </p>
                            </div>

                            <a
                                href="{{ route('terapis.show', $t['profile']) }}"
                                class="group/button inline-flex items-center gap-2 rounded-full bg-daun px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-daun-tua hover:shadow-md"
                            >
                                Lihat profil

                                <span class="transition-transform group-hover/button:translate-x-0.5">
                                    <x-icon name="arrow-right" class="h-4 w-4" />
                                </span>
                            </a>
                        </div>
                    </div>

                    {{-- Garis aksen bawah --}}
                    <div class="h-1 w-full origin-left scale-x-0 bg-daun transition-transform duration-300 group-hover:scale-x-100"></div>
                </article>
            @endforeach
        </div>

        {{-- Empty state --}}
        <div
            x-show="cat && !has.includes(cat)"
            x-cloak
            class="mt-8 overflow-hidden rounded-[28px] border border-dashed border-daun/30 bg-daun/5 p-10 text-center"
        >
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-white text-daun shadow-sm">
                <svg
                    viewBox="0 0 24 24"
                    class="h-8 w-8"
                    fill="currentColor"
                >
                    <path d="M20 4C10 4 4 10 4 20c8 0 16-6 16-16Z"/>
                </svg>
            </div>

            <h3 class="mt-4 font-display text-lg font-bold text-arang">
                Terapis belum tersedia
            </h3>

            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-kabut">
                Belum ada contoh terapis untuk
                <span class="font-semibold text-arang" x-text="labels[cat]"></span>
                di wilayah ini.
            </p>

            <button
                type="button"
                @click="cat = ''"
                class="mt-5 rounded-full bg-daun px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-daun-tua"
            >
                Tampilkan semua terapis
            </button>
        </div>

        {{-- Tombol mobile --}}
        <div class="mt-8 text-center sm:hidden">
            <a
                href="/cari"
                class="inline-flex items-center gap-2 rounded-full border border-daun/25 bg-white px-6 py-3 text-sm font-bold text-daun shadow-sm transition-colors hover:bg-daun/5"
            >
                Lihat semua terapis
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
</section>

{{-- ============ VERIFIKASI (signature) ============ --}}
@php
    $badgeImages = [
        'badge_anggota_kumitas.webp',
        'badge_identitas_verifikasi.webp',
        'badge_terapis_terverifikasi.webp',
        'badge_terapis_berpengalaman.webp',
        'badge_terapis_pilihan.webp',
    ];
@endphp

<section id="verifikasi" class="bg-white py-14">
    <div class="mx-auto max-w-6xl px-4">
        <div class="max-w-xl">
            <span class="chip">Segel daun</span>

            <h2 class="mt-3 font-display text-3xl font-bold text-arang sm:text-4xl">
                Kepercayaan yang berjenjang
            </h2>

            <p class="mt-3 text-kabut">
                Setiap terapis naik tingkat lewat pemeriksaan dokumen dan rekam
                jejak — bukan dibeli.
            </p>
        </div>

        <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($ladder as $i => $row)
                <li
                    class="relative overflow-hidden rounded-2xl p-4 transition-transform duration-200 hover:-translate-y-1
                    {{ $i === 4 ? 'bg-daun text-white' : 'bg-kertas' }}"
                >
                    <span
                        class="absolute left-4 top-4 grid h-7 w-7 place-items-center rounded-full text-xs font-bold
                        {{ $i === 4 ? 'bg-white text-daun' : 'bg-daun text-white' }}"
                    >
                        {{ $i + 1 }}
                    </span>

                    <div class="flex min-h-32 items-center justify-center pt-5">
                        <img
                            src="{{ asset('storage/' . $badgeImages[$i]) }}"
                            alt="{{ $row[0] }}"
                            class="h-28 w-28 object-contain sm:h-32 sm:w-32"
                            loading="lazy"
                        >
                    </div>

                    <div class="mt-4 text-center">
                        <p class="text-sm font-bold {{ $i === 4 ? 'text-white' : 'text-arang' }}">
                            {{ $row[0] }}
                        </p>

                        <p class="mt-1 text-xs leading-5 {{ $i === 4 ? 'text-white/80' : 'text-kabut' }}">
                            {{ $row[1] }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ============ CARA KERJA (urutan → bernomor) ============ --}}
<section id="cara-kerja" class="mx-auto max-w-6xl px-4 py-14">
    <h2 class="font-display text-3xl font-bold text-arang sm:text-4xl">Tiga langkah, selesai</h2>
    <div class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ($steps as $i => $s)
            <div class="rounded-card border border-garis bg-white p-6">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-daun-muda text-daun">
                        <x-icon :name="$s[0]" class="h-6 w-6" />
                    </span>
                    <span class="font-display text-2xl font-bold text-daun-muda">0{{ $i + 1 }}</span>
                </div>
                <p class="mt-4 text-lg font-semibold text-arang">{{ $s[1] }}</p>
                <p class="mt-1 text-sm text-kabut">{{ $s[2] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ============ TESTIMONI (wajah asli, humanis) ============ --}}
<section class="bg-daun-muda py-14">
    <div class="mx-auto max-w-6xl px-4">
        <div class="flex items-end justify-between gap-4">
            <div>
                <span class="chip">Cerita pengguna</span>
                <h2 class="mt-3 font-display text-3xl font-bold text-arang sm:text-4xl">Pengalaman memesan lewat GoTerapis</h2>
            </div>
            <div class="hidden items-center gap-1.5 sm:flex">
                <svg viewBox="0 0 24 24" class="h-5 w-5 text-kunyit" fill="currentColor"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                <span class="font-bold text-arang">4,9</span>
                <span class="text-sm text-kabut">dari 12rb+ ulasan</span>
            </div>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($testimonials as $t)
                <figure class="flex flex-col rounded-card border border-garis bg-white p-6">
                    <div class="flex gap-0.5 text-kunyit">
                        @for ($i = 0; $i < 5; $i++)
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                        @endfor
                    </div>
                    <blockquote class="mt-4 flex-1 text-arang">“{{ $t['teks'] }}”</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3 border-t border-garis pt-4">
                        <img src="{{ $u }}photo-{{ $t['foto'] }}?auto=format&fit=crop&crop=faces&q=70&w=96&h=96"
                             alt="{{ $t['nama'] }}" loading="lazy" class="h-11 w-11 rounded-full object-cover">
                        <div>
                            <p class="text-sm font-semibold text-arang">{{ $t['nama'] }}</p>
                            <p class="text-xs text-kabut">{{ $t['kota'] }}</p>
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FAQ (accordion) ============ --}}
<section class="mx-auto max-w-3xl px-4 py-14">
    <h2 class="font-display text-3xl font-bold text-arang sm:text-4xl">Pertanyaan umum</h2>
    <div class="mt-6 divide-y divide-garis rounded-card border border-garis bg-white" x-data="{ open: 0 }">
        @foreach ([
            ['Apakah pembayaran aman?', 'Dana kamu ditahan platform dan baru diteruskan ke terapis setelah kamu mengonfirmasi layanan selesai.'],
            ['Bagaimana terapis diperiksa?', 'Admin meninjau dokumen yang diajukan terapis sebelum memberikan status verifikasi yang sesuai.'],
            ['Bisa panggil ke rumah?', 'Bisa. Terapis panggilan datang ke lokasimu sesuai wilayah layanan; ada juga terapis yang menerima di tempat praktik.'],
            ['Apakah ini layanan medis?', 'Bukan. GoTerapis adalah layanan kebugaran dan terapi tradisional, bukan pengganti dokter atau klinik.'],
        ] as $i => $f)
            <div class="p-4">
                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full items-center justify-between gap-4 text-left">
                    <span class="font-semibold text-arang">{{ $f[0] }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         class="h-5 w-5 shrink-0 text-daun transition-transform" :class="open === {{ $i }} && 'rotate-45'"><path d="M12 5v14M5 12h14"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse x-cloak>
                    <p class="pt-3 text-sm text-kabut">{{ $f[1] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ============ CTA TERAPIS ============ --}}
<section class="mx-auto max-w-6xl px-4 pb-16">
    <div class="rounded-card bg-daun px-6 py-10 text-center sm:px-10 sm:py-14">
        <h2 class="mx-auto max-w-lg font-display text-3xl font-bold text-white sm:text-4xl">Punya keahlian terapi? Bangun reputasimu di GoTerapis.</h2>
        <p class="mx-auto mt-3 max-w-md text-white/80">Gratis bergabung. Dapatkan pelanggan, kelola jadwal, dan terima pembayaran yang tercatat.</p>
        <a href="/daftar-terapis" class="mt-6 inline-block rounded-full bg-kunyit px-6 py-3 font-semibold text-arang transition-colors hover:bg-white">Daftar jadi terapis</a>
    </div>
</section>
</div>{{-- /x-data --}}
@endsection
