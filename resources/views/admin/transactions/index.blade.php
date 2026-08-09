@extends('layouts.admin')
@section('title', 'Transaksi')
@section('heading', 'Transaksi')
@section('subheading', 'Rekonsiliasi status pembayaran dan pesanan')
@section('content')
@php
    $labels = ['pending' => 'Menunggu', 'paid' => 'Lunas', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'refunded' => 'Dikembalikan'];
@endphp
<div class="flex flex-col gap-4">
    <div class="flex flex-wrap gap-2">
        @foreach ([null => 'Semua', ...$labels] as $value => $label)
            <a href="{{ route('admin.transactions.index', array_filter(['status' => $value])) }}" class="rounded-xl border px-3.5 py-2.5 text-xs font-semibold {{ $status === $value ? 'border-arang bg-arang text-white' : 'border-garis bg-white text-kabut' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="kartu overflow-hidden">
        @forelse ($payments as $payment)
            <a href="{{ route('admin.transactions.show', $payment) }}" class="grid gap-2 border-b border-garis px-5 py-4 last:border-0 hover:bg-kertas sm:grid-cols-[1.2fr_1fr_1fr_1fr] sm:items-center">
                <span><strong class="block text-sm text-arang">{{ $payment->order->code }}</strong><small class="text-kabut">{{ $payment->order->user->name }}</small></span>
                <span class="text-xs text-kabut">Pesanan: {{ $payment->order->status }}</span>
                <span class="text-xs font-bold text-arang">Pembayaran: {{ $labels[$payment->status] ?? $payment->status }}</span>
                <span class="text-right text-sm font-bold text-arang">Rp{{ number_format($payment->amount, 0, ',', '.') }}</span>
            </a>
        @empty
            <p class="p-10 text-center text-sm text-kabut">Belum ada transaksi.</p>
        @endforelse
        <div class="border-t border-garis px-5 py-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
