@extends('layouts.admin')
@section('title', 'Detail transaksi')
@section('heading', 'Detail transaksi')
@section('subheading', $payment->order->code)
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <section class="kartu p-6">
        <h2 class="font-display mb-4 text-lg font-extrabold text-arang">Pembayaran</h2>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <dt class="text-kabut">Status</dt><dd class="font-bold text-arang">{{ $payment->status }}</dd>
            <dt class="text-kabut">Gateway</dt><dd class="font-bold text-arang">{{ $payment->gateway }}</dd>
            <dt class="text-kabut">Referensi</dt><dd class="break-all font-bold text-arang">{{ $payment->gateway_ref }}</dd>
            <dt class="text-kabut">Nominal</dt><dd class="font-bold text-arang">Rp{{ number_format($payment->amount, 0, ',', '.') }}</dd>
            <dt class="text-kabut">Percobaan refund</dt><dd class="font-bold text-arang">{{ $payment->refund_attempts }}</dd>
        </dl>
        @if ($payment->refund_error)
            <div class="mt-5 rounded-xl border border-jahe bg-jahe-muda p-4 text-sm text-jahe">{{ $payment->refund_error }}</div>
        @endif
        @if ($payment->canRetryRefund())
            <form method="post" action="{{ route('admin.transactions.retry-refund', $payment) }}" class="mt-5">
                @csrf
                <button class="rounded-xl bg-daun px-4 py-3 text-sm font-bold text-white">Coba ulang refund</button>
            </form>
        @endif
    </section>
    <section class="kartu p-6">
        <h2 class="font-display mb-4 text-lg font-extrabold text-arang">Pesanan</h2>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <dt class="text-kabut">Status</dt><dd class="font-bold text-arang">{{ $payment->order->status }}</dd>
            <dt class="text-kabut">Pelanggan</dt><dd class="font-bold text-arang">{{ $payment->order->user->name }}</dd>
            <dt class="text-kabut">Terapis</dt><dd class="font-bold text-arang">{{ $payment->order->therapistProfile->user->name }}</dd>
            <dt class="text-kabut">Layanan</dt><dd class="font-bold text-arang">{{ $payment->order->service->name }}</dd>
            <dt class="text-kabut">Dibuat</dt><dd class="font-bold text-arang">{{ $payment->created_at->translatedFormat('j M Y H:i') }}</dd>
        </dl>
    </section>
</div>
@endsection
