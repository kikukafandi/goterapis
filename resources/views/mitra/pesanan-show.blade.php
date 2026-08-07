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
        'in_progress' => 'Sesi berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Sengketa',
    ];
    $isNegative = in_array($order->status, ['rejected', 'cancelled', 'refunded', 'disputed'], true);
    $isPanggilan = $order->model === 'panggilan';
@endphp

@section('content')
{{-- Header solid — pembeda visual sisi mitra --}}
<div class="bg-daun px-4 pb-7 pt-5">
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('mitra.pesanan') }}" aria-label="Kembali ke daftar pesanan"
               class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25">←</a>
            <span class="flex min-w-0 flex-col gap-1">
                <span class="text-[11px] font-medium text-white/70">Nomor pesanan</span>
                <span class="font-display truncate text-base font-extrabold text-white">{{ $order->code }}</span>
            </span>
            <span class="ml-auto shrink-0 rounded-full bg-white/15 px-3 py-2 text-[10px] font-bold text-white">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
        </div>

        <div class="flex items-end justify-between gap-4">
            <span class="flex flex-col gap-1">
                <span class="text-[11px] font-medium text-white/70">Nilai pesanan</span>
                <span class="font-display text-[32px] font-extrabold leading-none text-white">Rp{{ number_format($order->price + $order->transport_fee, 0, ',', '.') }}</span>
            </span>
            <span class="pb-1 text-right text-[11px] font-medium leading-snug text-white/70">
                Layanan Rp{{ number_format($order->price, 0, ',', '.') }}@if ($order->transport_fee > 0)<br>Transport Rp{{ number_format($order->transport_fee, 0, ',', '.') }}@endif
            </span>
        </div>
    </div>
</div>

<div class="mx-auto flex max-w-3xl flex-col gap-3.5 px-4 pb-28 pt-4">

    {{-- Data pasien --}}
    <div class="kartu flex flex-col gap-3.5 p-[18px]">
        <div class="flex items-center gap-3">
            @if ($order->user->avatarUrl())
                <img src="{{ $order->user->avatarUrl() }}" alt="" loading="lazy" class="h-13 w-13 shrink-0 rounded-[15px] object-cover">
            @else
                <span class="grid h-13 w-13 shrink-0 place-items-center rounded-[15px] bg-daun-muda text-lg font-extrabold text-daun">{{ mb_substr($order->user->name, 0, 1) }}</span>
            @endif
            <span class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="truncate text-sm font-bold text-arang">{{ $order->user->name }}</span>
                <span class="truncate text-xs font-medium text-kabut-muda">{{ $order->service->name }} · {{ $order->duration_min }} menit</span>
            </span>
        </div>
        <div class="flex gap-2.5">
            <a href="#chat-pesanan" class="flex-1 rounded-[14px] border-[1.5px] border-garis bg-white py-3.5 text-center text-[13px] font-bold text-arang hover:bg-kertas">Chat</a>
            @if ($order->user->phone)
                <a href="tel:{{ $order->user->phone }}" class="flex-1 rounded-[14px] border-[1.5px] border-garis bg-white py-3.5 text-center text-[13px] font-bold text-arang hover:bg-kertas">Telepon</a>
            @endif
        </div>
    </div>

    {{-- Jadwal, alamat, catatan --}}
    <div class="kartu flex flex-col gap-2.5 p-[18px]">
        <span class="text-sm font-bold text-arang">Detail sesi</span>
        <div class="flex justify-between gap-3.5">
            <span class="shrink-0 text-[13px] font-medium text-kabut-muda">Jadwal</span>
            <span class="text-right text-[13px] font-semibold leading-snug text-arang">{{ $order->scheduled_at->translatedFormat('l, d F Y · H:i') }}</span>
        </div>
        <div class="flex justify-between gap-3.5">
            <span class="shrink-0 text-[13px] font-medium text-kabut-muda">Model</span>
            <span class="text-right text-[13px] font-semibold text-arang">{{ $isPanggilan ? 'Panggilan ke rumah' : 'Di tempat praktik' }}</span>
        </div>
        @if ($order->address)
            <div class="flex justify-between gap-3.5">
                <span class="shrink-0 text-[13px] font-medium text-kabut-muda">Alamat</span>
                <span class="text-right text-[13px] font-semibold leading-snug text-arang">{{ $order->address }}</span>
            </div>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}" target="_blank" rel="noopener"
               class="btn-garis mt-1 w-full py-3.5 text-[13px]">Buka di peta</a>
        @endif
        @if ($order->notes)
            <div class="mt-1 flex flex-col gap-1.5 rounded-2xl bg-kunyit-muda px-4 py-3">
                <span class="text-[10px] font-bold uppercase tracking-[.06em] text-kunyit-tua">Catatan keluhan</span>
                <span class="text-[13px] font-medium leading-relaxed text-arang">{{ $order->notes }}</span>
            </div>
        @endif
    </div>

    {{-- Aksi berurutan: satu tombol utama besar per tahap --}}
    @if ($order->status === 'paid' && $isPanggilan)
        <form method="post" action="{{ route('mitra.pesanan.en-route', $order) }}">
            @csrf @method('patch')
            <button class="btn-utama w-full text-[15px]">Berangkat (OTW)</button>
        </form>
    @elseif ($order->status === 'therapist_en_route' && $isPanggilan)
        <div class="kartu flex flex-col gap-3.5 border-daun-garis p-[18px]"
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
                <span class="denyut mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-daun-terang"></span>
                <span class="flex flex-col gap-1">
                    <span class="text-[13px] font-bold text-daun-tua" x-text="state"></span>
                    <span class="text-[11px] font-medium leading-relaxed text-kabut-muda">Biarkan halaman ini tetap terbuka selama perjalanan agar lokasi terus diperbarui.</span>
                </span>
            </div>
            <form method="post" action="{{ route('mitra.pesanan.arrive', $order) }}" @submit="stop()">
                @csrf @method('patch')
                <button class="btn-utama w-full text-[15px]">Saya tiba</button>
            </form>
        </div>
    @endif

    @if (($order->status === 'therapist_arrived' && $isPanggilan) || ($order->status === 'paid' && ! $isPanggilan))
        <form method="post" action="{{ route('mitra.pesanan.start', $order) }}" class="kartu flex flex-col gap-2.5 p-[18px]"
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
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-semibold text-arang">PIN dari pasien</span>
                <input name="pin" inputmode="numeric" maxlength="6" required placeholder="6 digit" class="isian tracking-[.2em]">
            </label>
            <button :disabled="loading" class="btn-utama w-full text-[15px]" x-text="loading ? 'Mengecek lokasi…' : 'Mulai sesi'"></button>
            <p x-show="error" x-text="error" role="alert" class="text-xs font-medium text-jahe"></p>
        </form>
    @endif

    @if ($isNegative)
        <div class="flex gap-2.5 rounded-card border border-jahe-garis bg-jahe-muda px-4 py-3.5">
            <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-jahe-terang"></span>
            <span class="text-[13px] font-medium leading-relaxed text-jahe">
                Pesanan ini {{ mb_strtolower($statusLabels[$order->status] ?? $order->status) }}.
                @if ($order->cancel_reason) Alasan: {{ $order->cancel_reason }} @endif
            </span>
        </div>
    @endif

    <div id="chat-pesanan" class="scroll-mt-24">
        <x-order-chat :$order :$messages />
    </div>
</div>
@endsection
