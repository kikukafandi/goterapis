@extends('layouts.admin')
@section('title', 'Ringkasan Operasional')
@section('heading', 'Ringkasan Operasional')
@section('content')
@php
    $needsAttention = $stats['pending_docs'] + $stats['open_reports'];
    $statusCounts = $latest->countBy('verification_status');
    $latestCount = $latest->count();
    $metrics = [
        ['label' => 'Total pesanan', 'value' => $stats['orders'], 'icon' => 'clipboard', 'note' => 'Seluruh transaksi tercatat'],
        ['label' => 'Total pengguna', 'value' => $stats['users'], 'icon' => 'user', 'note' => 'Akun pelanggan terdaftar'],
        ['label' => 'Total terapis', 'value' => $stats['therapists'], 'icon' => 'leaf', 'note' => 'Seluruh mitra terdaftar', 'href' => route('admin.therapists')],
        ['label' => 'Total produk', 'value' => $stats['products'], 'icon' => 'image', 'note' => 'Item dalam katalog', 'href' => route('admin.products.index')],
    ];
    $statusStyles = [
        'anggota' => 'bg-kabut',
        'identitas' => 'bg-daun',
        'berpengalaman' => 'bg-kunyit',
        'terdaftar' => 'bg-daun-tua',
        'pilihan' => 'bg-jahe',
    ];
@endphp

<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-garis pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-kabut">Selamat datang, {{ auth()->user()->name }}</p>
            <h2 class="mt-1 font-display text-2xl font-bold text-arang sm:text-3xl">Ringkasan Operasional</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-kabut">Pantau kondisi platform, antrean verifikasi, dan aktivitas mitra GoTerapis.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex min-h-10 items-center gap-2 rounded-full border border-garis bg-white px-4 text-xs font-semibold text-kabut">
                <x-icon name="calendar" class="h-4 w-4 text-daun" /> {{ now()->translatedFormat('l, d F Y') }}
            </span>
            <a href="{{ route('admin.therapists') }}" class="inline-flex min-h-10 items-center gap-2 rounded-full bg-daun px-4 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">
                Tinjau terapis <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </header>

    <section aria-labelledby="attention-heading" class="rounded-card border {{ $needsAttention ? 'border-kunyit/50 bg-kunyit-muda/50' : 'border-daun/20 bg-daun-muda/60' }} p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $needsAttention ? 'bg-kunyit text-arang' : 'bg-daun text-white' }}">
                    <x-icon name="shield" class="h-5 w-5" />
                </span>
                <div>
                    <h3 id="attention-heading" class="font-display text-lg font-bold text-arang">Perlu Ditindaklanjuti</h3>
                    <p class="mt-1 text-sm text-kabut">{{ $needsAttention ? 'Beberapa aktivitas membutuhkan pemeriksaan admin.' : 'Tidak ada antrean yang membutuhkan perhatian saat ini.' }}</p>
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-[34rem]">
                <a href="{{ route('admin.therapists') }}" class="group flex items-center gap-3 rounded-xl border border-garis bg-white p-3.5 transition-colors hover:border-daun">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-daun-muda text-daun"><x-icon name="clipboard" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1"><p class="text-xl font-bold leading-none text-arang">{{ number_format($stats['pending_docs'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-kabut">Dokumen menunggu tinjauan</p></div>
                    <x-icon name="arrow-right" class="h-4 w-4 text-kabut group-hover:text-daun" />
                </a>
                <div class="flex items-center gap-3 rounded-xl border border-garis bg-white p-3.5">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-kunyit-muda text-kunyit"><x-icon name="shield" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1"><p class="text-xl font-bold leading-none text-arang">{{ number_format($stats['open_reports'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-kabut">Laporan terbuka</p></div>
                    <span class="rounded-full bg-kertas px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-kabut">Pantau</span>
                </div>
            </div>
        </div>
    </section>

    <section aria-labelledby="platform-heading">
        <div class="mb-3 flex items-end justify-between gap-4">
            <div><h3 id="platform-heading" class="font-display text-lg font-bold text-arang">Kondisi platform</h3><p class="mt-0.5 text-xs text-kabut">Total data yang tercatat hingga hari ini.</p></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                @if (isset($metric['href']))<a href="{{ $metric['href'] }}" class="group rounded-card border border-garis bg-white p-4 transition-colors hover:border-daun">@else<div class="rounded-card border border-garis bg-white p-4">@endif
                    <div class="flex items-start justify-between gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-daun-muda text-daun"><x-icon :name="$metric['icon']" class="h-4 w-4" /></span>
                        @if (isset($metric['href']))<x-icon name="arrow-right" class="h-4 w-4 text-garis transition-colors group-hover:text-daun" />@endif
                    </div>
                    <p class="mt-4 text-2xl font-bold leading-none text-arang sm:text-3xl">{{ number_format($metric['value'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm font-semibold text-arang">{{ $metric['label'] }}</p>
                    <p class="mt-0.5 text-xs text-kabut">{{ $metric['note'] }}</p>
                @if (isset($metric['href']))</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-12">
        <section aria-labelledby="latest-heading" class="overflow-hidden rounded-card border border-garis bg-white xl:col-span-8">
            <div class="flex items-start justify-between gap-4 border-b border-garis px-5 py-4 sm:px-6">
                <div><h3 id="latest-heading" class="font-display text-lg font-bold text-arang">Terapis terbaru</h3><p class="mt-1 text-xs text-kabut">Pendaftaran mitra terbaru dan status pemeriksaannya.</p></div>
                <a href="{{ route('admin.therapists') }}" class="shrink-0 text-sm font-semibold text-daun hover:underline">Lihat semua</a>
            </div>
            <div>
                @forelse ($latest as $therapist)
                    <a href="{{ route('admin.therapist', $therapist) }}" class="group flex flex-col gap-3 border-b border-garis px-5 py-4 transition-colors last:border-0 hover:bg-kertas sm:flex-row sm:items-center sm:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            @if ($therapist->user->avatarUrl())
                                <img src="{{ $therapist->user->avatarUrl() }}" alt="" loading="lazy" class="h-11 w-11 shrink-0 rounded-full object-cover">
                            @else
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-daun-muda font-bold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
                            @endif
                            <div class="min-w-0"><p class="truncate text-sm font-semibold text-arang">{{ $therapist->user->name }}</p><p class="mt-0.5 truncate text-xs text-kabut">{{ $therapist->city ?? 'Wilayah belum diisi' }} · Terdaftar {{ $therapist->created_at->diffForHumans() }}</p></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <span class="rounded-full bg-daun-muda px-2.5 py-1 text-[11px] font-semibold text-daun-tua">{{ $therapist->statusLabel() }}</span>
                            @if ($therapist->pending_count)
                                <span class="rounded-full bg-kunyit-muda px-2.5 py-1 text-[11px] font-semibold text-arang">{{ $therapist->pending_count }} dokumen menunggu</span>
                            @else
                                <span class="rounded-full bg-kertas px-2.5 py-1 text-[11px] font-medium text-kabut">Dokumen tertangani</span>
                            @endif
                            <x-icon name="arrow-right" class="hidden h-4 w-4 text-garis transition-colors group-hover:text-daun sm:block" />
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="user" class="h-5 w-5" /></span><p class="mt-3 text-sm font-semibold text-arang">Belum ada terapis terdaftar</p><p class="mt-1 text-xs text-kabut">Pendaftaran terapis baru akan muncul di sini.</p></div>
                @endforelse
            </div>
        </section>

        <div class="space-y-6 xl:col-span-4">
            <section aria-labelledby="distribution-heading" class="rounded-card border border-garis bg-white p-5">
                <div><h3 id="distribution-heading" class="font-display text-lg font-bold text-arang">Status pendaftaran terbaru</h3><p class="mt-1 text-xs text-kabut">Distribusi dari {{ $latestCount }} terapis terbaru.</p></div>
                @if ($latestCount)
                    <div class="mt-5 flex h-2.5 overflow-hidden rounded-full bg-kertas" aria-hidden="true">
                        @foreach ($statusCounts as $status => $count)<span class="{{ $statusStyles[$status] ?? 'bg-kabut' }}" style="width: {{ ($count / $latestCount) * 100 }}%"></span>@endforeach
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach ($statusCounts as $status => $count)
                            <div class="flex items-center gap-3 text-sm"><span class="h-2.5 w-2.5 rounded-full {{ $statusStyles[$status] ?? 'bg-kabut' }}"></span><span class="min-w-0 flex-1 truncate text-kabut">{{ \App\Models\TherapistProfile::STATUS_LABELS[$status] ?? $status }}</span><strong class="text-arang">{{ $count }}</strong></div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-xl bg-kertas p-5 text-center"><p class="text-sm font-semibold text-arang">Data belum tersedia</p><p class="mt-1 text-xs leading-5 text-kabut">Distribusi status akan tampil setelah ada pendaftaran terapis.</p></div>
                @endif
            </section>

            <section aria-labelledby="store-heading" class="rounded-card border border-garis bg-daun-tua p-5 text-white">
                <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-kunyit">Etalase</p><h3 id="store-heading" class="mt-1 font-display text-lg font-bold">Kontrol toko</h3></div><span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10"><x-icon name="image" class="h-5 w-5" /></span></div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.products.index') }}" class="rounded-xl bg-white/10 p-3 transition-colors hover:bg-white/15"><p class="text-xl font-bold">{{ number_format($stats['products'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-white/70">Produk katalog</p></a>
                    <a href="{{ route('admin.banners.index') }}" class="rounded-xl bg-white/10 p-3 transition-colors hover:bg-white/15"><p class="text-xl font-bold">{{ number_format($stats['active_banners'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-white/70">Banner aktif</p></a>
                </div>
                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold"><a href="{{ route('admin.products.index') }}" class="text-kunyit hover:underline">Kelola produk</a><a href="{{ route('admin.banners.index') }}" class="text-kunyit hover:underline">Kelola banner</a><a href="{{ route('products.index') }}" target="_blank" rel="noopener" class="text-white/80 hover:text-white">Lihat toko ↗</a></div>
            </section>
        </div>
    </div>

    <section aria-labelledby="insight-heading" class="rounded-card border border-garis bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 id="insight-heading" class="font-display text-lg font-bold text-arang">Performa transaksi</h3><p class="mt-1 text-xs text-kabut">Grafik pendapatan dan distribusi layanan belum tersedia pada sumber data dashboard saat ini.</p></div>
            <span class="inline-flex w-fit items-center rounded-full bg-kertas px-3 py-1.5 text-xs font-semibold text-kabut">Menunggu data periode transaksi</span>
        </div>
        <div class="mt-5 grid gap-4 lg:grid-cols-[1.5fr_1fr]">
            <div class="flex min-h-40 items-center justify-center rounded-xl border border-dashed border-garis bg-kertas/60 p-6 text-center"><div><x-icon name="clipboard" class="mx-auto h-6 w-6 text-daun" /><p class="mt-3 text-sm font-semibold text-arang">Belum ada ringkasan berkala</p><p class="mt-1 max-w-sm text-xs leading-5 text-kabut">Total pesanan saat ini {{ number_format($stats['orders'], 0, ',', '.') }}. Grafik akan akurat setelah data periode tersedia.</p></div></div>
            <div class="flex min-h-40 items-center justify-center rounded-xl border border-dashed border-garis bg-kertas/60 p-6 text-center"><div><x-icon name="clipboard" class="mx-auto h-6 w-6 text-daun" /><p class="mt-3 text-sm font-semibold text-arang">Distribusi layanan belum tersedia</p><p class="mt-1 max-w-sm text-xs leading-5 text-kabut">Kategori layanan belum disertakan dalam data dashboard.</p></div></div>
        </div>
    </section>
</div>
@endsection
