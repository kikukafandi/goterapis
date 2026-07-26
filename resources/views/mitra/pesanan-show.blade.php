@extends('layouts.app')
@section('title', 'Pesanan '.$order->code)

@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6">
    <a href="{{ route('mitra.pesanan') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">← Kembali ke pesanan</a>

    <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
        <p class="text-xs text-kabut">Nomor pesanan</p>
        <h1 class="font-display text-xl font-bold text-arang">{{ $order->code }}</h1>
        <dl class="mt-4 space-y-2 border-t border-garis pt-4 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-kabut">Pelanggan</dt><dd class="font-semibold text-arang">{{ $order->user->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-kabut">Layanan</dt><dd class="text-right font-semibold text-arang">{{ $order->service->name }} · {{ $order->duration_min }} menit</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-kabut">Jadwal</dt><dd class="text-right font-semibold text-arang">{{ $order->scheduled_at->translatedFormat('l, d F Y · H:i') }}</dd></div>
            @if ($order->address)<div class="flex justify-between gap-4"><dt class="text-kabut">Alamat</dt><dd class="text-right text-arang">{{ $order->address }}</dd></div>@endif
            @if ($order->notes)<div class="flex justify-between gap-4"><dt class="text-kabut">Catatan</dt><dd class="text-right text-arang">{{ $order->notes }}</dd></div>@endif
        </dl>
    </div>

    <div class="mt-5"><x-order-chat :$order :$messages /></div>
</div>
@endsection
