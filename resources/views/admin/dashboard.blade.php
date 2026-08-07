@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Ringkasan platform dan antrean yang menunggu tindakanmu')
@section('content')
@php
    $butuhTindakan = $stats['pending_docs'] + $stats['open_reports'] + $stats['pending_withdrawals'];

    $kartuStatistik = [
        ['label' => 'Pengguna', 'value' => $stats['users'], 'note' => 'Akun role user'],
        ['label' => 'Terapis', 'value' => $stats['therapists'], 'note' => 'Profil mitra terdaftar'],
        ['label' => 'Dokumen menunggu', 'value' => $stats['pending_docs'], 'note' => 'Belum ditinjau', 'awas' => true],
        ['label' => 'Pesanan', 'value' => $stats['orders'], 'note' => 'Sepanjang waktu'],
        ['label' => 'Produk', 'value' => $stats['products'], 'note' => 'Katalog toko'],
        ['label' => 'Banner aktif', 'value' => $stats['active_banners'], 'note' => 'Sedang tayang'],
        ['label' => 'Laporan terbuka', 'value' => $stats['open_reports'], 'note' => 'Perlu ditindak', 'awas' => true],
    ];

    $antrean = [
        [
            'label' => 'Dokumen menunggu tinjauan',
            'note' => 'Dari pendaftaran terapis baru',
            'count' => $stats['pending_docs'],
            'dot' => 'bg-jahe-terang',
            'href' => route('admin.therapists'),
        ],
        [
            'label' => 'Penarikan menunggu',
            'note' => 'Total Rp'.number_format($stats['pending_withdrawal_total'], 0, ',', '.'),
            'count' => $stats['pending_withdrawals'],
            'dot' => 'bg-kunyit',
            'href' => route('admin.withdrawals.index'),
        ],
        [
            'label' => 'Laporan pengguna terbuka',
            'note' => 'Sengketa pesanan',
            'count' => $stats['open_reports'],
            'dot' => 'bg-jahe-terang',
            'href' => route('admin.therapists'),
        ],
    ];

    $konten = [
        ['label' => 'Produk aktif', 'value' => $stats['products']],
        ['label' => 'Banner tayang', 'value' => $stats['active_banners']],
        ['label' => 'Artikel terbit', 'value' => $stats['articles']],
    ];
@endphp

<div class="flex flex-col gap-6">

    {{-- Kartu statistik --}}
    <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kartuStatistik as $s)
            <div class="kartu flex flex-col gap-2.5 p-5">
                <span class="text-[11px] font-semibold text-kabut-muda">{{ $s['label'] }}</span>
                <span class="font-display text-3xl font-extrabold {{ ($s['awas'] ?? false) && $s['value'] > 0 ? 'text-jahe' : 'text-arang' }}">{{ number_format($s['value'], 0, ',', '.') }}</span>
                <span class="text-[11px] font-medium leading-snug text-kabut-samar">{{ $s['note'] }}</span>
            </div>
        @endforeach

        <div class="flex flex-col justify-center gap-2.5 rounded-card bg-malam p-5">
            <span class="text-[11px] font-semibold text-white/50">Butuh tindakanmu</span>
            <span class="font-display text-3xl font-extrabold text-daun-neon">{{ number_format($butuhTindakan, 0, ',', '.') }}</span>
            <a href="{{ route('admin.therapists') }}" class="mt-1 rounded-[10px] bg-white/10 py-2.5 text-center text-[11px] font-bold text-white hover:bg-white/20">Tinjau sekarang</a>
        </div>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[1.5fr_1fr]">

        {{-- Terapis terbaru --}}
        <section class="kartu overflow-hidden">
            <div class="flex items-baseline justify-between gap-4 border-b border-garis-muda px-5 py-5 sm:px-6">
                <h2 class="font-display text-base font-extrabold text-arang">Terapis terbaru</h2>
                <a href="{{ route('admin.therapists') }}" class="shrink-0 text-xs font-bold text-daun hover:text-daun-tua">Lihat semua</a>
            </div>

            <div class="hidden grid-cols-[2fr_1.4fr_1fr_90px] gap-3.5 border-b border-garis-muda bg-kertas-isian px-6 py-3 sm:grid">
                @foreach (['Terapis', 'Status', 'Bergabung', 'Dokumen'] as $h)
                    <span class="text-[10px] font-bold uppercase tracking-[.06em] text-kabut-samar">{{ $h }}</span>
                @endforeach
            </div>

            @forelse ($latest as $therapist)
                <a href="{{ route('admin.therapist', $therapist) }}"
                   class="grid gap-3.5 border-b border-garis-muda px-5 py-3.5 last:border-0 hover:bg-kertas-isian sm:grid-cols-[2fr_1.4fr_1fr_90px] sm:items-center sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($therapist->user->avatarUrl())
                            <img src="{{ $therapist->user->avatarUrl() }}" alt="" loading="lazy" class="h-9 w-9 shrink-0 rounded-full object-cover">
                        @else
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-daun-muda text-xs font-bold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
                        @endif
                        <span class="flex min-w-0 flex-col gap-1">
                            <span class="truncate text-[13px] font-bold text-arang">{{ $therapist->user->name }}</span>
                            <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $therapist->city ?? 'Wilayah belum diisi' }}</span>
                        </span>
                    </div>
                    <span class="text-[11px] font-semibold {{ $therapist->verification_status === 'pilihan' ? 'text-daun' : 'text-kabut' }}">{{ $therapist->statusLabel() }}</span>
                    <span class="text-[11px] font-medium text-kabut-muda">{{ $therapist->created_at->translatedFormat('j M Y') }}</span>
                    @if ($therapist->pending_count)
                        <span class="justify-self-start rounded-full bg-kunyit-muda px-2.5 py-1.5 text-[10px] font-bold text-kunyit-tua">{{ $therapist->pending_count }} dok</span>
                    @else
                        <span class="text-[11px] font-medium text-kabut-samar">—</span>
                    @endif
                </a>
            @empty
                <div class="flex flex-col items-center gap-3 px-10 py-16 text-center">
                    <span class="grid h-[76px] w-[76px] place-items-center rounded-full bg-garis-muda text-kabut-samar"><x-icon name="user" class="h-7 w-7" /></span>
                    <p class="font-display text-base font-extrabold text-arang">Belum ada terapis terdaftar</p>
                    <p class="max-w-xs text-xs leading-relaxed text-kabut-muda">Pendaftaran mitra baru akan muncul di sini begitu ada yang mendaftar.</p>
                </div>
            @endforelse
        </section>

        <div class="flex flex-col gap-5">
            {{-- Antrean kerja --}}
            <section class="kartu flex flex-col gap-4 p-5 sm:p-6">
                <h2 class="font-display text-base font-extrabold text-arang">Antrean kerja</h2>
                @foreach ($antrean as $q)
                    <a href="{{ $q['href'] }}" class="flex items-center gap-3.5 rounded-2xl border border-garis-muda bg-kertas-isian px-4 py-3.5 hover:border-daun-terang">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $q['count'] > 0 ? $q['dot'] : 'bg-kabut-samar' }}"></span>
                        <span class="flex min-w-0 flex-1 flex-col gap-1">
                            <span class="truncate text-[13px] font-bold text-arang">{{ $q['label'] }}</span>
                            <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $q['note'] }}</span>
                        </span>
                        <span class="font-display shrink-0 text-lg font-extrabold text-arang">{{ number_format($q['count'], 0, ',', '.') }}</span>
                    </a>
                @endforeach
            </section>

            {{-- Konten aktif --}}
            <section class="kartu flex flex-col gap-3.5 p-5 sm:p-6">
                <h2 class="font-display text-base font-extrabold text-arang">Konten aktif</h2>
                @foreach ($konten as $c)
                    <div class="flex items-center justify-between gap-3 border-b border-garis-muda pb-3 last:border-0 last:pb-0">
                        <span class="text-[13px] font-medium text-kabut">{{ $c['label'] }}</span>
                        <span class="text-sm font-bold text-arang">{{ number_format($c['value'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex flex-wrap gap-x-4 gap-y-2 pt-1 text-xs font-bold">
                    <a href="{{ route('admin.products.index') }}" class="text-daun hover:text-daun-tua">Kelola produk</a>
                    <a href="{{ route('admin.banners.index') }}" class="text-daun hover:text-daun-tua">Kelola banner</a>
                    <a href="{{ route('admin.articles.index') }}" class="text-daun hover:text-daun-tua">Kelola artikel</a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
