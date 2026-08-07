@extends('layouts.admin')
@section('title', 'Terapis')
@section('heading', 'Terapis')
@section('subheading', 'Kelola verifikasi, badge, dan status tampil di beranda')
@section('content')
@php
    $adaFilter = request('q') || request('status');
    $filterStatus = ['' => 'Semua'] + [
        'anggota' => 'Anggota',
        'identitas' => 'Identitas',
        'berpengalaman' => 'Berpengalaman',
        'terdaftar' => 'Terdaftar',
        'pilihan' => 'Pilihan',
    ];
@endphp

<div class="flex flex-col gap-4">

    {{-- Pencarian + filter status --}}
    <form method="get" class="flex flex-wrap items-center gap-3">
        <label class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-garis bg-white px-3.5 py-3 sm:max-w-sm">
            <span class="sr-only">Cari nama atau nomor telepon</span>
            <x-icon name="search" class="h-4 w-4 shrink-0 text-kabut-samar" />
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor telepon"
                   class="w-full bg-transparent text-[13px] font-medium outline-none placeholder:text-kabut-samar">
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach ($filterStatus as $key => $label)
                @php $aktif = (string) request('status') === (string) $key; @endphp
                <a href="{{ route('admin.therapists', array_filter(['q' => request('q'), 'status' => $key ?: null])) }}"
                   class="whitespace-nowrap rounded-[10px] border px-3.5 py-2.5 text-xs font-semibold {{ $aktif ? 'border-arang bg-arang text-white' : 'border-garis bg-white text-kabut hover:border-daun-terang' }}">{{ $label }}</a>
            @endforeach
        </div>
        <button class="rounded-[10px] bg-daun px-4 py-2.5 text-xs font-bold text-white hover:bg-daun-tua">Cari</button>
    </form>

    <div class="kartu overflow-hidden">
        {{-- Kepala tabel (desktop) --}}
        <div class="hidden grid-cols-[2fr_1.3fr_1.1fr_1fr_100px_110px] gap-3.5 border-b border-garis-muda bg-kertas-isian px-6 py-3.5 lg:grid">
            @foreach (['Terapis', 'Status verifikasi', 'Kota', 'Rating', 'Dokumen', 'Bergabung'] as $h)
                <span class="text-[10px] font-bold uppercase tracking-[.06em] text-kabut-samar">{{ $h }}</span>
            @endforeach
        </div>

        @forelse ($therapists as $therapist)
            <a href="{{ route('admin.therapist', $therapist) }}"
               class="grid gap-3.5 border-b border-garis-muda px-4 py-3.5 last:border-0 hover:bg-kertas-isian lg:grid-cols-[2fr_1.3fr_1.1fr_1fr_100px_110px] lg:items-center lg:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($therapist->user->avatarUrl())
                        <img src="{{ $therapist->user->avatarUrl() }}" alt="" loading="lazy" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    @else
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-daun-muda text-xs font-bold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
                    @endif
                    <span class="flex min-w-0 flex-col gap-1">
                        <span class="truncate text-[13px] font-bold text-arang">{{ $therapist->user->name }}</span>
                        <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $therapist->user->phone ?? 'Nomor belum diisi' }}</span>
                    </span>
                </div>
                <span class="text-[11px] font-semibold {{ $therapist->verification_status === 'pilihan' ? 'text-daun' : 'text-kabut' }}">{{ $therapist->statusLabel() }}</span>
                <span class="text-xs font-medium text-kabut">{{ $therapist->city ?? '—' }}</span>
                <span class="text-xs font-medium text-kabut">{{ $therapist->rating_avg ? number_format($therapist->rating_avg, 1, ',', '.') : '—' }}</span>
                @if ($therapist->pending_count)
                    <span class="justify-self-start rounded-full bg-kunyit-muda px-2.5 py-1.5 text-[10px] font-bold text-kunyit-tua">{{ $therapist->pending_count }} dok</span>
                @else
                    <span class="text-[11px] font-medium text-kabut-samar">—</span>
                @endif
                <span class="text-[11px] font-medium text-kabut-samar">{{ $therapist->created_at->translatedFormat('j M Y') }}</span>
            </a>
        @empty
            <div class="flex flex-col items-center gap-3 px-10 py-16 text-center">
                <span class="grid h-[76px] w-[76px] place-items-center rounded-full bg-garis-muda text-kabut-samar">
                    <x-icon name="{{ $adaFilter ? 'search' : 'leaf' }}" class="h-7 w-7" />
                </span>
                <p class="font-display text-base font-extrabold text-arang">{{ $adaFilter ? 'Tidak ada terapis yang cocok' : 'Belum ada terapis terdaftar' }}</p>
                <p class="max-w-xs text-xs leading-relaxed text-kabut-muda">{{ $adaFilter ? 'Coba kata kunci lain atau pilih status "Semua".' : 'Pendaftaran terapis baru akan muncul di sini.' }}</p>
                @if ($adaFilter)
                    <a href="{{ route('admin.therapists') }}" class="mt-1 rounded-xl bg-arang px-5 py-3 text-xs font-bold text-white">Hapus semua filter</a>
                @endif
            </div>
        @endforelse

        @if ($therapists->hasPages())
            <div class="border-t border-garis-muda px-4 py-4 sm:px-6">{{ $therapists->links() }}</div>
        @else
            <p class="px-4 py-4 text-xs font-medium text-kabut-samar sm:px-6">Menampilkan {{ $therapists->count() }} dari {{ $therapists->total() }} terapis</p>
        @endif
    </div>
</div>
@endsection
