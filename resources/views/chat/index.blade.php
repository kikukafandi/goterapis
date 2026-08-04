@extends('layouts.app')
@section('title', 'Percakapan')

@php
    $statusLabels = [
        'pending_confirmation' => 'Menunggu konfirmasi terapis',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'therapist_en_route' => 'Terapis sedang OTW',
        'therapist_arrived' => 'Terapis sudah tiba',
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
    <h1 class="font-display text-3xl font-bold text-arang">Percakapan</h1>
    <p class="mt-2 text-base leading-relaxed text-kabut">Pilih percakapan untuk membaca dan mengirim pesan.</p>

    <div class="mt-6 space-y-4">
        @forelse ($orders as $order)
            @php
                $isTherapist = auth()->user()->isTherapist();
                $counterpart = $isTherapist ? $order->user : $order->therapistProfile->user;
                $message = $order->latestMessage;
            @endphp
            <a href="{{ route($isTherapist ? 'mitra.pesanan.show' : 'pesanan.show', $order) }}"
               class="block min-h-32 rounded-card border-2 border-garis bg-white p-5 transition-colors hover:border-daun focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-kunyit">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-display text-xl font-bold text-arang">{{ $counterpart->name }}</p>
                        <p class="mt-1 text-sm font-semibold text-daun">{{ $order->service->name }} · {{ $statusLabels[$order->status] ?? $order->status }}</p>
                    </div>
                    @if ($order->unread_messages_count)
                        <span class="shrink-0 rounded-full bg-kunyit px-3 py-1.5 text-sm font-bold text-arang">{{ $order->unread_messages_count }} pesan baru</span>
                    @endif
                </div>
                <div class="mt-4 flex items-end justify-between gap-4">
                    <p class="line-clamp-2 text-base leading-relaxed text-arang">{{ $message?->body ?? 'Belum ada pesan. Ketuk untuk memulai chat.' }}</p>
                    <time class="shrink-0 text-sm text-kabut" datetime="{{ ($message?->created_at ?? $order->created_at)->toIso8601String() }}">{{ ($message?->created_at ?? $order->created_at)->diffForHumans() }}</time>
                </div>
            </a>
        @empty
            <div class="rounded-card border-2 border-garis bg-white p-8 text-center">
                <x-icon name="chat" class="mx-auto h-12 w-12 text-daun" />
                <h2 class="mt-4 font-display text-xl font-bold text-arang">Belum ada percakapan</h2>
                <p class="mt-2 text-base leading-relaxed text-kabut">Percakapan dengan terapis akan muncul setelah kamu membuat pesanan.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
