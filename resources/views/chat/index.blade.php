@extends('layouts.app')
@section('title', auth()->user()->isTherapist() ? 'Pesan' : 'Percakapan')

@php
    $isTherapist = auth()->user()->isTherapist();
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
@endphp

@section('content')
@if ($isTherapist)
    <div class="bg-daun-terang px-5 pb-5 pt-4">
        <h1 class="font-display mx-auto max-w-2xl text-[22px] font-extrabold text-white">Pesan</h1>
    </div>
@endif
<div class="mx-auto max-w-2xl px-5 pb-28 {{ $isTherapist ? 'pt-0' : 'pt-6' }}">
    @unless ($isTherapist)
        <h1 class="font-display text-3xl font-bold text-arang">Percakapan</h1>
        <p class="mt-2 text-base leading-relaxed text-kabut">Pilih percakapan untuk membaca dan mengirim pesan.</p>
    @endunless

    <div class="{{ $isTherapist ? '' : 'mt-6 space-y-4' }}">
        @forelse ($orders as $order)
            @php
                $isSeller = $order->therapistProfile->user_id === auth()->id();
                $counterpart = $isSeller ? $order->user : $order->therapistProfile->user;
                $message = $order->latestMessage;
            @endphp
            <a href="{{ route($isSeller ? 'mitra.pesanan.show' : 'pesanan.show', $order) }}"
               class="flex items-center gap-3 border-b border-garis-muda py-[15px] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-kunyit {{ $isTherapist ? '' : 'rounded-card border border-garis bg-white px-5' }}">
                @if ($counterpart->avatarUrl())
                    <img src="{{ $counterpart->avatarUrl() }}" alt="" loading="lazy" class="h-[50px] w-[50px] shrink-0 rounded-full object-cover">
                @else
                    <span class="grid h-[50px] w-[50px] shrink-0 place-items-center rounded-full bg-daun-muda text-base font-extrabold text-daun">{{ mb_substr($counterpart->name, 0, 1) }}</span>
                @endif
                <span class="min-w-0 flex-1">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="truncate text-sm font-bold text-arang">{{ $counterpart->name }}</span>
                        <time class="shrink-0 text-[11px] text-kabut-samar" datetime="{{ ($message?->created_at ?? $order->created_at)->toIso8601String() }}">{{ ($message?->created_at ?? $order->created_at)->diffForHumans() }}</time>
                    </span>
                    <span class="mt-1 block truncate text-[10px] font-semibold text-daun">{{ $order->service->name }} · {{ $statusLabels[$order->status] ?? $order->status }}</span>
                    <span class="mt-1 flex items-center gap-2">
                        <span class="min-w-0 flex-1 truncate text-xs text-kabut-muda">{{ $message?->body ?? 'Belum ada pesan. Ketuk untuk memulai chat.' }}</span>
                        @if ($order->unread_messages_count)
                            <span class="grid min-h-[19px] min-w-[19px] shrink-0 place-items-center rounded-full bg-daun px-1 text-[10px] font-bold text-white" aria-label="{{ $order->unread_messages_count }} pesan baru">{{ $order->unread_messages_count }}</span>
                        @endif
                    </span>
                </span>
            </a>
        @empty
            <div class="px-6 py-12 text-center {{ $isTherapist ? '' : 'rounded-card border border-garis bg-white' }}">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="chat" class="h-5 w-5" /></span>
                <h2 class="mt-3 font-display text-lg font-bold text-arang">Belum ada percakapan</h2>
                <p class="mt-1 text-xs leading-relaxed text-kabut-muda">{{ $isTherapist ? 'Percakapan pelanggan akan muncul saat ada pesanan.' : 'Percakapan dengan terapis akan muncul setelah kamu membuat pesanan.' }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
