@extends('layouts.app')
@section('title', 'Pesanan')

@php
    $statusLabels = [
        'pending_confirmation' => 'Menunggu konfirmasi',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'therapist_en_route' => 'Sedang OTW',
        'therapist_arrived' => 'Sudah tiba',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'in_progress' => 'Sesi berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Sengketa',
    ];
    $badgeClasses = [
        'pending_confirmation' => 'bg-kunyit-muda text-kunyit-tua',
        'completed' => 'bg-garis-muda text-kabut',
        'rejected' => 'bg-jahe-muda text-jahe',
        'cancelled' => 'bg-jahe-muda text-jahe',
        'refunded' => 'bg-jahe-muda text-jahe',
        'disputed' => 'bg-jahe-muda text-jahe',
    ];
@endphp

@section('content')
<div class="relative overflow-hidden bg-daun-terang px-5 pb-5 pt-4 sm:px-8 sm:pb-6 sm:pt-5">
    <span class="pointer-events-none absolute -right-12 -top-16 h-40 w-40 rounded-full bg-white/10 lg:-right-20 lg:-top-44 lg:h-96 lg:w-96"></span>
    <h1 class="font-display mx-auto max-w-6xl text-[22px] font-extrabold text-white sm:text-2xl">Pesanan</h1>
</div>

<div class="mx-auto max-w-6xl px-5 pb-28 pt-3 sm:px-8 sm:pt-5 lg:pb-16">
    <nav aria-label="Status pesanan" class="grid grid-cols-3 gap-1.5 rounded-[14px] bg-garis-muda p-1 lg:max-w-xl">
        @foreach (['baru' => 'Baru', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $key => $label)
            <a href="{{ route('mitra.pesanan', ['tab' => $key]) }}"
               @class([
                    'rounded-[11px] px-2 py-3 text-center text-xs font-bold transition-colors',
                    'bg-white text-arang' => $tab === $key,
                    'text-kabut-muda hover:text-arang' => $tab !== $key,
                ])
               @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <a href="{{ route('mitra.pesanan.show', $order) }}" class="kartu block p-[15px] transition-colors hover:border-daun-garis sm:p-4">
                <div class="flex items-center justify-between gap-2.5">
                    <span class="font-mono text-[11px] font-semibold text-kabut-samar">{{ $order->code }}</span>
                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-2 text-[10px] font-bold',
                        $badgeClasses[$order->status] ?? 'bg-daun-muda text-daun',
                    ])>{{ $statusLabels[$order->status] ?? $order->status }}</span>
                </div>
                <div class="mt-3 flex items-center gap-3">
                    @if ($order->user->avatarUrl())
                        <img src="{{ $order->user->avatarUrl() }}" alt="" loading="lazy" class="h-[46px] w-[46px] shrink-0 rounded-[14px] object-cover">
                    @else
                        <span class="grid h-[46px] w-[46px] shrink-0 place-items-center rounded-[14px] bg-daun-muda text-sm font-extrabold text-daun">{{ mb_substr($order->user->name, 0, 1) }}</span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-arang">{{ $order->user->name }}</span>
                        <span class="mt-1 block truncate text-xs font-medium text-kabut-muda">{{ $order->service->name }} · {{ $order->duration_min }} menit</span>
                        <span class="mt-1 block truncate text-[11px] font-medium text-kabut-samar">{{ $order->scheduled_at->isToday() ? 'Hari ini' : $order->scheduled_at->translatedFormat('d M') }} · {{ $order->scheduled_at->format('H:i') }} · {{ $order->model === 'panggilan' ? 'Panggilan' : 'Tempat praktik' }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-[10px] font-medium text-kabut-samar">Bayaran</span>
                        <span class="font-display mt-1 block text-[15px] font-extrabold text-arang">Rp{{ number_format($order->payout, 0, ',', '.') }}</span>
                    </span>
                </div>
            </a>
        @empty
            <div class="kartu px-6 py-10 text-center md:col-span-2 xl:col-span-3">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="calendar" class="h-5 w-5" /></span>
                <p class="mt-3 text-sm font-bold text-arang">Belum ada pesanan {{ $tab }}</p>
                <p class="mt-1 text-xs leading-relaxed text-kabut-muda">Pesanan akan muncul di sini saat statusnya sesuai.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
