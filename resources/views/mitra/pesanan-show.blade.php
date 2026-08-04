@extends('layouts.app')
@section('title', 'Pesanan '.$order->code)

@php
    $statusLabels = [
        'pending_confirmation' => 'Perlu konfirmasi',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'therapist_en_route' => 'Sedang OTW',
        'therapist_arrived' => 'Sudah tiba',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'in_progress' => 'Berlangsung',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Sengketa',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6">
    <a href="{{ route('mitra.pesanan') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">← Kembali ke pesanan</a>

    <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
        <p class="text-xs text-kabut">Nomor pesanan</p>
        <div class="flex items-start justify-between gap-4">
            <h1 class="font-display text-xl font-bold text-arang">{{ $order->code }}</h1>
            <span class="shrink-0 rounded-full bg-kunyit/15 px-2.5 py-1 text-xs font-semibold text-arang">{{ $statusLabels[$order->status] ?? $order->status }}</span>
        </div>
        <dl class="mt-4 space-y-2 border-t border-garis pt-4 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-kabut">Pelanggan</dt><dd class="font-semibold text-arang">{{ $order->user->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-kabut">Layanan</dt><dd class="text-right font-semibold text-arang">{{ $order->service->name }} · {{ $order->duration_min }} menit</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-kabut">Jadwal</dt><dd class="text-right font-semibold text-arang">{{ $order->scheduled_at->translatedFormat('l, d F Y · H:i') }}</dd></div>
            @if ($order->address)<div class="flex justify-between gap-4"><dt class="text-kabut">Alamat</dt><dd class="text-right text-arang">{{ $order->address }}</dd></div>@endif
            @if ($order->notes)<div class="flex justify-between gap-4"><dt class="text-kabut">Catatan</dt><dd class="text-right text-arang">{{ $order->notes }}</dd></div>@endif
        </dl>
        @if ($order->status === 'paid' && $order->model === 'panggilan')
            <form method="post" action="{{ route('mitra.pesanan.en-route', $order) }}" class="mt-4">
                @csrf @method('patch')
                <button class="w-full rounded-full bg-kunyit px-6 py-3 text-sm font-semibold text-arang">Saya sedang OTW</button>
            </form>
        @elseif ($order->status === 'therapist_en_route' && $order->model === 'panggilan')
            <div class="mt-4 rounded-xl border border-daun/25 bg-daun-muda p-4"
                 x-data="{
                    watchId: null, lastSent: 0, state: 'Meminta akses lokasi…',
                    init() {
                        if (! navigator.geolocation) { this.state = 'Browser ini tidak mendukung pelacakan lokasi.'; return }
                        this.watchId = navigator.geolocation.watchPosition(position => this.send(position), error => {
                            this.state = error.code === 1 ? 'Akses lokasi ditolak. Izinkan lokasi di pengaturan browser.' : 'Lokasi belum ditemukan. Pastikan GPS aktif.'
                        }, { enableHighAccuracy: true, maximumAge: 3000, timeout: 15000 });
                        window.addEventListener('pagehide', () => this.stop(), { once: true });
                    },
                    async send(position) {
                        if (Date.now() - this.lastSent < 5000) return;
                        this.lastSent = Date.now();
                        try {
                            const response = await fetch(@js(route('mitra.pesanan.location', $order)), {
                                method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                                body: JSON.stringify({ lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy })
                            });
                            if (! response.ok) throw new Error();
                            this.state = 'Pelacakan aktif · diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        } catch { this.state = 'Lokasi gagal dikirim. Mencoba lagi saat GPS diperbarui.' }
                    },
                    stop() { if (this.watchId !== null) navigator.geolocation.clearWatch(this.watchId) }
                 }">
                <div class="flex gap-3">
                    <x-icon name="pin" class="mt-0.5 h-5 w-5 shrink-0 text-daun" />
                    <div><p class="text-sm font-semibold text-daun-tua" x-text="state"></p><p class="mt-1 text-xs text-kabut">Biarkan halaman ini tetap terbuka selama perjalanan agar lokasi terus diperbarui.</p></div>
                </div>
                <form method="post" action="{{ route('mitra.pesanan.arrive', $order) }}" class="mt-4" @submit="stop()">
                    @csrf @method('patch')
                    <button class="w-full rounded-full bg-kunyit px-6 py-3 text-sm font-semibold text-arang">Saya sudah tiba</button>
                </form>
            </div>
        @endif
        @if (($order->status === 'therapist_arrived' && $order->model === 'panggilan') || ($order->status === 'paid' && $order->model !== 'panggilan'))
            <form method="post" action="{{ route('mitra.pesanan.start', $order) }}" class="mt-4 space-y-2"
                  @submit.prevent="go($el)"
                  x-data="{
                    loading: false, error: '',
                    go(form) {
                        if (! navigator.geolocation) { this.error = 'Browser ini tidak mendukung lokasi.'; return }
                        this.loading = true; this.error = '';
                        navigator.geolocation.getCurrentPosition(
                            position => {
                                form.querySelector('[name=lat]').value = position.coords.latitude.toFixed(7);
                                form.querySelector('[name=lng]').value = position.coords.longitude.toFixed(7);
                                form.querySelector('[name=acc]').value = Math.round(position.coords.accuracy);
                                form.submit();
                            },
                            error => {
                                this.loading = false;
                                this.error = error.code === 1
                                    ? 'Izinkan akses lokasi untuk situs ini di pengaturan browser.'
                                    : 'Lokasi belum ditemukan. Pastikan GPS aktif lalu coba lagi.';
                            },
                            { enableHighAccuracy: true, timeout: 15000 }
                        );
                    }
                  }">
                @csrf @method('patch')
                <input type="hidden" name="lat">
                <input type="hidden" name="lng">
                <input type="hidden" name="acc">
                <input name="pin" inputmode="numeric" maxlength="6" required placeholder="PIN 6 digit" class="w-full rounded-xl border border-garis px-3 py-2.5 text-sm">
                <button :disabled="loading" class="w-full rounded-full bg-daun px-6 py-3 text-sm font-semibold text-white disabled:opacity-60" x-text="loading ? 'Mengecek lokasi…' : 'Mulai layanan'"></button>
                <p x-show="error" x-text="error" role="alert" class="text-sm text-jahe"></p>
            </form>
        @endif
    </div>

    <div class="mt-5"><x-order-chat :$order :$messages /></div>
</div>
@endsection
