@extends('layouts.app')
@section('title', 'Pesanan masuk')

@php
    $statusLabels = [
        'pending_confirmation' => 'Perlu konfirmasi',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'in_progress' => 'Berlangsung',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Sengketa',
    ];
    $actionable = ['pending_confirmation'];
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 pb-28 pt-6">
    <h1 class="font-display text-2xl font-bold text-arang">Pesanan masuk</h1>
    <p class="mt-1 text-sm text-kabut">Tinjau jadwal dan detail pelanggan sebelum menerima atau menolak pesanan.</p>

    <div class="mt-6 space-y-3">
        @forelse ($orders as $order)
            <div class="rounded-card border border-garis bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-arang">{{ $order->service->name }} · {{ $order->duration_min }} menit</p>
                        <p class="text-xs text-kabut">{{ $order->user->name }} · {{ $order->code }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-kunyit/15 px-2.5 py-1 text-xs font-semibold text-arang">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>

                <dl class="mt-3 space-y-1.5 border-t border-garis pt-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-kabut">Jadwal</dt><dd class="text-right font-semibold text-arang">{{ $order->scheduled_at->translatedFormat('l, d M Y · H:i') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-kabut">Model</dt><dd class="text-right font-semibold text-arang">{{ $order->model === 'panggilan' ? 'Panggilan' : 'Tempat praktik' }}</dd></div>
                    @if ($order->address)
                        <div class="flex justify-between gap-4"><dt class="text-kabut">Alamat</dt><dd class="text-right text-arang">{{ $order->address }}</dd></div>
                    @endif
                    @if ($order->notes)
                        <div class="flex justify-between gap-4"><dt class="text-kabut">Catatan</dt><dd class="text-right text-arang">{{ $order->notes }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-kabut">Bagianmu</dt><dd class="text-right font-bold text-daun">Rp{{ number_format($order->payout, 0, ',', '.') }}</dd></div>
                </dl>

                <a href="{{ route('mitra.pesanan.show', $order) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">
                    Lihat detail dan percakapan <span aria-hidden="true">→</span>
                </a>

                @if (in_array($order->status, $actionable, true))
                    <div class="mt-4 flex gap-2">
                        <form method="post" action="{{ route('mitra.pesanan.accept', $order) }}" class="flex-1">
                            @csrf @method('patch')
                            <button class="w-full rounded-full bg-daun px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">Terima</button>
                        </form>
                        <form method="post" action="{{ route('mitra.pesanan.reject', $order) }}" class="flex-1">
                            @csrf @method('patch')
                            <button class="w-full rounded-full border border-garis px-4 py-2.5 text-sm font-semibold text-arang transition-colors hover:border-jahe hover:text-jahe">Tolak</button>
                        </form>
                    </div>
                @elseif ($order->status === 'pending_payment')
                    <p class="mt-4 rounded-xl border border-kunyit/30 bg-kunyit/10 px-4 py-3 text-sm text-arang">
                        Sudah kamu terima — menunggu pelanggan membayar.
                        @if ($batasBayar = $order->paymentDeadline())
                            <span class="mt-0.5 block text-xs text-kabut">Batal otomatis bila belum dibayar sampai {{ $batasBayar->translatedFormat('d M Y · H:i') }}.</span>
                        @endif
                    </p>
                @elseif (in_array($order->status, ['paid', 'accepted'], true))
                    <form method="post" action="{{ route('mitra.pesanan.start', $order) }}" class="mt-4"
                          @submit.prevent="go($el)"
                          x-data="{
                            sent: false, loading: false,
                            go(f) {
                                if (this.sent) { return }
                                if (! navigator.geolocation) { this.sent = true; f.submit(); return }
                                this.loading = true;
                                navigator.geolocation.getCurrentPosition(
                                    p => { f.querySelector('[name=lat]').value = p.coords.latitude.toFixed(7);
                                           f.querySelector('[name=lng]').value = p.coords.longitude.toFixed(7);
                                           f.querySelector('[name=acc]').value = Math.round(p.coords.accuracy);
                                           this.sent = true; f.submit(); },
                                    () => { this.sent = true; f.submit(); },
                                    { enableHighAccuracy: true, timeout: 10000 }
                                );
                            }
                          }">
                        @csrf @method('patch')
                        <input type="hidden" name="lat">
                        <input type="hidden" name="lng">
                        <input type="hidden" name="acc">
                        <label class="block text-sm font-semibold text-arang">Mulai layanan</label>
                        <p class="mt-0.5 text-xs text-kabut">
                            Minta 6 digit PIN ke pelanggan.
                            @if ($order->model === 'panggilan' && $order->lat)Lokasimu dicek harus di titik pelanggan.@endif
                        </p>
                        <div class="mt-2 flex gap-2">
                            <input name="pin" inputmode="numeric" maxlength="6" placeholder="PIN 6 digit" required
                                   class="w-32 rounded-full border border-garis bg-white px-4 py-2.5 text-center text-sm tracking-widest text-arang outline-none focus:border-daun">
                            <button class="flex-1 rounded-full bg-daun px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-daun-tua disabled:opacity-60"
                                    :disabled="loading">
                                <span x-text="loading ? 'Mengecek lokasi…' : 'Mulai layanan'"></span>
                            </button>
                        </div>
                    </form>
                @elseif ($order->status === 'in_progress')
                    <p class="mt-4 rounded-xl border border-daun/25 bg-daun-muda px-4 py-3 text-sm text-daun-tua">Sedang berlangsung — menunggu pelanggan mengonfirmasi selesai.</p>
                @endif
            </div>
        @empty
            <div class="rounded-card border border-garis bg-white p-8 text-center">
                <p class="text-sm text-kabut">Belum ada pesanan masuk.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
