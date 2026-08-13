@extends('layouts.app')
@section('title', 'Pesanan saya')

@php
    $statusLabels = [
        'pending_confirmation' => 'Menunggu konfirmasi',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'therapist_en_route' => 'Terapis OTW',
        'therapist_arrived' => 'Terapis tiba',
        'accepted' => 'Diterima terapis',
        'rejected' => 'Ditolak terapis',
        'in_progress' => 'Sesi berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Dalam sengketa',
    ];
    // Warna badge: hijau untuk selesai, clay untuk batal/sengketa, kuning untuk yang masih berjalan.
    $badgeStyle = fn (string $status) => match ($status) {
        'completed' => 'bg-daun-muda text-daun-tua',
        'rejected', 'cancelled', 'refunded', 'disputed' => 'bg-jahe-muda text-jahe',
        default => 'bg-kunyit-muda text-kunyit-tua',
    };
@endphp

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-4 px-4 pb-28 pt-6">
    <h1 class="font-display text-[22px] font-extrabold text-arang">Pesanan saya</h1>

    <div class="flex flex-col gap-3">
        @forelse ($orders as $order)
            <a href="{{ route('pesanan.show', $order) }}"
               class="kartu flex flex-col gap-3.5 p-4 transition-colors hover:border-daun-terang">
                <div class="flex items-center justify-between gap-2.5">
                    <span class="font-mono text-[11px] font-semibold text-kabut-samar">{{ $order->code }}</span>
                    <span class="shrink-0 rounded-full px-2.5 py-1.5 text-[10px] font-bold {{ $badgeStyle($order->status) }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    @if ($order->therapistProfile->user->avatarUrl())
                        <img src="{{ $order->therapistProfile->user->avatarUrl() }}" alt="" loading="lazy" class="h-13 w-13 shrink-0 rounded-[15px] object-cover">
                    @else
                        <span class="grid h-13 w-13 shrink-0 place-items-center rounded-[15px] bg-daun-muda text-lg font-extrabold text-daun">{{ mb_substr($order->therapistProfile->user->name, 0, 1) }}</span>
                    @endif
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="truncate text-sm font-bold leading-tight text-arang">{{ $order->therapistProfile->user->name }}</span>
                        <span class="truncate text-xs font-medium text-kabut-muda">{{ $order->service->name }}</span>
                        <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $order->scheduled_at->translatedFormat('d M Y · H:i') }}</span>
                    </span>
                    <span class="font-display shrink-0 text-sm font-extrabold text-arang">Rp{{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </a>
        @empty
            <div class="flex flex-col items-center gap-3.5 px-8 py-14 text-center">
                <span class="grid h-24 w-24 place-items-center rounded-[32px] bg-garis-muda text-kabut-samar">
                    <x-icon name="clipboard" class="h-9 w-9" />
                </span>
                <p class="font-display text-[17px] font-extrabold text-arang">Belum ada pesanan</p>
                <p class="max-w-xs text-[13px] font-medium leading-relaxed text-kabut-muda text-pretty">Cari terapis di sekitarmu dan atur jadwal yang paling pas.</p>
                <a href="{{ route('cari') }}" class="btn-utama mt-1 text-[13px]">Cari terapis</a>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
