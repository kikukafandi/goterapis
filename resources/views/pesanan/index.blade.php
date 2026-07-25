@extends('layouts.app')
@section('title', 'Riwayat pesanan')

@php
    $statusLabels = [
        'pending_confirmation' => 'Menunggu konfirmasi terapis',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'accepted' => 'Diterima terapis',
        'rejected' => 'Ditolak terapis',
        'in_progress' => 'Sedang berlangsung',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Dalam sengketa',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 pb-28 pt-6">
    <h1 class="font-display text-2xl font-bold text-arang">Riwayat pesanan</h1>

    <div class="mt-6 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('pesanan.show', $order) }}"
               class="block rounded-card border border-garis bg-white p-4 transition-colors hover:border-daun">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-arang">{{ $order->service->name }}</p>
                        <p class="text-xs text-kabut">{{ $order->therapistProfile->user->name }} · {{ $order->scheduled_at->translatedFormat('d M Y · H:i') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-kunyit/15 px-2.5 py-1 text-xs font-semibold text-arang">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
                <p class="mt-2 text-sm font-bold text-daun">Rp{{ number_format($order->total, 0, ',', '.') }}</p>
            </a>
        @empty
            <div class="rounded-card border border-garis bg-white p-8 text-center">
                <p class="text-sm text-kabut">Belum ada pesanan.</p>
                <a href="{{ route('cari') }}" class="mt-3 inline-block rounded-full bg-daun px-5 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">Cari terapis</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
