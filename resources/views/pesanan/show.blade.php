@extends('layouts.app')
@section('title', 'Pesanan '.$order->code)

@php
    $statusLabels = [
        'pending_confirmation' => 'Menunggu konfirmasi terapis',
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'therapist_en_route' => 'Terapis sedang menuju lokasi',
        'therapist_arrived' => 'Terapis sudah tiba',
        'accepted' => 'Diterima terapis',
        'rejected' => 'Ditolak terapis',
        'in_progress' => 'Sesi sedang berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dana dikembalikan',
        'disputed' => 'Dalam sengketa',
    ];
    $t = $order->therapistProfile;

    $steps = [
        ['label' => 'Pesanan dibuat', 'at' => $order->created_at],
        ['label' => 'Diterima terapis', 'at' => $order->accepted_at],
        ['label' => 'Pembayaran', 'at' => $order->payment?->paid_at],
        ...($order->model === 'panggilan' ? [
            ['label' => 'Terapis OTW', 'at' => null],
            ['label' => 'Terapis tiba', 'at' => null],
        ] : []),
        ['label' => 'Sesi berjalan', 'at' => $order->started_at],
        ['label' => 'Selesai', 'at' => $order->completed_at],
    ];
    $currentStep = $order->model === 'panggilan' ? match ($order->status) {
        'pending_payment' => 1, 'paid', 'accepted' => 2, 'therapist_en_route' => 3, 'therapist_arrived' => 4, 'in_progress' => 5, 'completed' => 6, default => 0,
    } : match ($order->status) {
        'pending_payment' => 1, 'paid', 'accepted' => 2, 'in_progress' => 3, 'completed' => 4, default => 0,
    };
    $negatives = ['rejected', 'cancelled', 'refunded', 'disputed'];
    $isNegative = in_array($order->status, $negatives, true);

    $hints = [
        'pending_confirmation' => 'Menunggu terapis mengonfirmasi. Kamu baru membayar setelah pesanan diterima.',
        'pending_payment' => 'Terapis sudah menerima. Selesaikan pembayaran — dana ditahan platform sampai layanan selesai.',
        'paid' => 'Pembayaran diterima. Siapkan dirimu sesuai jadwal.',
        'therapist_en_route' => 'Terapis sedang menuju lokasimu.',
        'therapist_arrived' => 'Terapis sudah tiba. Berikan PIN untuk memulai layanan.',
        'accepted' => 'Terapis sudah menerima. Siapkan dirimu sesuai jadwal.',
        'in_progress' => 'Layanan sedang berlangsung.',
        'completed' => 'Layanan selesai. Terima kasih! Beri ulasan untuk terapis.',
        'rejected' => 'Terapis tidak dapat menerima pesanan ini. Kamu bisa mencari terapis lain.',
        'cancelled' => 'Pesanan ini dibatalkan. Buat pesanan baru jika masih membutuhkan layanan.',
        'refunded' => 'Pengembalian dana telah diproses.',
        'disputed' => 'Pesanan sedang ditinjau admin. Pantau halaman ini untuk pembaruan.',
    ];

    $heroStyle = match (true) {
        $isNegative => 'bg-jahe-muda border-jahe-garis',
        $order->status === 'completed' => 'bg-daun-muda border-daun-garis',
        default => 'bg-malam border-malam',
    };
    $heroDark = ! $isNegative && $order->status !== 'completed';
@endphp

@section('content')
<div class="mx-auto grid max-w-[1160px] grid-cols-1 gap-3.5 px-4 pb-28 pt-5 md:grid-cols-[minmax(0,1fr)_380px] md:gap-6 md:px-8 md:pt-8">

    <div class="flex items-center gap-3.5 md:col-span-2 md:items-end">
        <a href="{{ route('pesanan.index') }}" aria-label="Kembali ke riwayat pesanan"
           class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-garis bg-white text-arang hover:bg-kertas">←</a>
        <span class="flex min-w-0 flex-col gap-1">
            <span class="text-[11px] font-medium text-kabut-samar">Nomor pesanan</span>
            <span class="font-display truncate text-base font-extrabold text-arang">{{ $order->code }}</span>
        </span>
    </div>

    <div class="flex flex-col gap-3 rounded-card border p-5 md:col-start-1 md:p-7 {{ $heroStyle }}">
        <div class="flex items-center justify-between gap-3">
            <span class="font-display text-lg font-extrabold {{ $heroDark ? 'text-white' : ($isNegative ? 'text-jahe' : 'text-daun-tua') }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
            <span class="denyut h-2.5 w-2.5 shrink-0 rounded-full {{ $heroDark ? 'bg-daun-neon' : ($isNegative ? 'bg-jahe-terang' : 'bg-daun-terang') }}"></span>
        </div>
        <p class="text-[13px] font-medium leading-relaxed text-pretty {{ $heroDark ? 'text-white/60' : ($isNegative ? 'text-jahe' : 'text-daun-tua') }}">
            {{ $hints[$order->status] ?? '' }}
            @if ($isNegative && $order->cancel_reason)
                <span class="mt-1 block text-xs">Alasan: {{ $order->cancel_reason }}</span>
            @endif
        </p>
    </div>

    @if ($order->status === 'pending_payment' && ! $order->paymentExpired())
        <form method="post" action="{{ route('pesanan.pay', $order) }}" class="md:col-start-2 md:row-start-2">
            @csrf
            <button class="btn-utama w-full text-[15px]">Bayar sekarang · Rp{{ number_format($order->total, 0, ',', '.') }}</button>
            @if ($batasBayar = $order->paymentDeadline())
                <p class="mt-2 text-center text-[11px] font-medium leading-snug text-kabut-samar">
                    Bayar sebelum {{ $batasBayar->translatedFormat('d M Y · H:i') }} — lewat itu pesanan batal otomatis.
                </p>
            @endif
        </form>
    @elseif (in_array($order->status, ['paid', 'therapist_en_route', 'therapist_arrived', 'accepted'], true))
        <div class="flex flex-col items-center gap-2 rounded-card border-[1.5px] border-daun-garis bg-white p-[18px] md:col-start-2 md:row-start-2 md:self-start md:p-6">
            <span class="text-[11px] font-medium text-kabut-muda">PIN mulai layanan — berikan ke terapis</span>
            <span class="font-display text-[32px] font-extrabold tracking-[.28em] text-daun">{{ $order->start_pin }}</span>
        </div>
    @endif

    @if ($order->status === 'therapist_en_route' && $order->model === 'panggilan')
        <div class="kartu flex items-center gap-3.5 p-4 md:col-start-1 md:p-6"
             x-data="{
                location: @js($therapistLocation), now: Date.now(),
                init() {
                    setInterval(() => this.now = Date.now(), 10000);
                    window.Echo?.private('orders.{{ $order->id }}').listen('.therapist.location.updated', event => { this.location = event; this.now = Date.now() });
                },
                distance() {
                    if (! this.location) return 'Menunggu lokasi terapis…';
                    return this.location.distance_m < 1000 ? `${this.location.distance_m} m lagi` : `${(this.location.distance_m / 1000).toFixed(1).replace('.', ',')} km lagi`;
                },
                stale() { return ! this.location || this.now - new Date(this.location.updated_at).getTime() > 30000 }
             }">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-daun text-white"><x-icon name="pin" class="h-5 w-5" /></span>
            <span class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-[.06em] text-daun">Perjalanan terapis</span>
                <span class="font-display text-[19px] font-extrabold text-arang" x-text="distance()"></span>
                <span class="text-[11px] font-medium" :class="stale() ? 'text-jahe' : 'text-kabut-samar'"
                      x-text="! location ? 'Belum ada pembaruan lokasi' : (stale() ? 'Lokasi terakhir sudah agak lama' : `Diperbarui ${new Date(location.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} · akurasi ±${location.accuracy} m`)"></span>
            </span>
        </div>
    @endif

    @if ($order->status === 'completed')
        @if ($order->review)
            <div class="kartu flex flex-col gap-2 p-[18px] md:col-start-2">
                <span class="text-[11px] font-medium text-kabut-muda">Ulasanmu</span>
                <div class="flex items-center gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 {{ $i <= round($order->review->averageRating()) ? 'text-kunyit' : 'text-garis' }}"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                    @endfor
                </div>
                @if ($order->review->body)<p class="text-[13px] leading-relaxed text-arang">{{ $order->review->body }}</p>@endif
            </div>
        @else
            <form method="post" action="{{ route('pesanan.review', $order) }}" class="kartu flex flex-col gap-3 p-[18px] md:col-start-2 md:p-6"
                  x-data="{ rating: 0, hover: 0 }">
                @csrf
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-bold text-arang">Beri ulasan</span>
                    <span class="text-xs font-medium text-kabut-samar">Seberapa puas kamu dengan layanan ini?</span>
                </div>
                <input type="hidden" name="rating" :value="rating">
                <div class="flex items-center gap-1.5" @mouseleave="hover = 0">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}"
                                aria-label="Beri {{ $i }} bintang"
                                :class="{{ $i }} <= (hover || rating) ? 'text-kunyit' : 'text-garis'">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                        </button>
                    @endfor
                </div>
                <textarea name="body" rows="3" placeholder="Ceritakan pengalamanmu (opsional)…" class="isian resize-none leading-relaxed"></textarea>
                <button type="submit" x-bind:disabled="rating === 0" class="btn-utama w-full text-sm">Kirim ulasan</button>
            </form>
        @endif
    @endif

    @unless ($isNegative)
        <div class="kartu flex flex-col gap-4 p-[18px] md:col-start-1 md:p-6">
            <span class="text-sm font-bold text-arang">Riwayat status</span>
            <ol>
                @foreach ($steps as $i => $s)
                    @php $done = $i <= $currentStep; $active = $i === $currentStep; @endphp
                    <li class="flex gap-3.5">
                        <div class="flex shrink-0 flex-col items-center">
                            <span class="grid h-[26px] w-[26px] shrink-0 place-items-center rounded-full border-[1.5px] text-[11px] font-extrabold
                                         {{ $done ? 'border-daun bg-daun text-white' : 'border-garis bg-white text-kabut-samar' }}">
                                {{ $done && ! $active ? '✓' : $i + 1 }}
                            </span>
                            @unless ($loop->last)
                                <span class="mt-1 w-0.5 flex-1 {{ $i < $currentStep ? 'bg-daun' : 'bg-garis' }}" style="min-height:1rem"></span>
                            @endunless
                        </div>
                        <div class="flex flex-col gap-1 pb-2.5">
                            <span class="text-[13px] font-bold leading-tight {{ $done ? 'text-arang' : 'text-kabut-samar' }}">
                                {{ $s['label'] }}@if ($active)<span class="ml-1.5 text-[11px] font-semibold text-daun">• saat ini</span>@endif
                            </span>
                            @if ($s['at'])
                                <span class="text-[11px] font-medium text-kabut-samar">{{ $s['at']->translatedFormat('d M Y · H:i') }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endunless

    <div class="kartu flex flex-col gap-3.5 p-[18px] md:col-start-2 md:row-start-3 md:self-start md:p-6">
        <div class="flex items-center gap-3">
            @if ($t->user->avatarUrl())
                <img src="{{ $t->user->avatarUrl() }}" alt="" loading="lazy" class="h-[50px] w-[50px] shrink-0 rounded-[15px] object-cover">
            @else
                <span class="grid h-[50px] w-[50px] shrink-0 place-items-center rounded-[15px] bg-daun-muda text-lg font-extrabold text-daun">{{ mb_substr($t->user->name, 0, 1) }}</span>
            @endif
            <span class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="truncate text-sm font-bold text-arang">{{ $t->user->name }}</span>
                <span class="truncate text-xs font-medium text-kabut-muda">{{ $order->service->name }} · {{ $order->duration_min }} menit</span>
            </span>
        </div>
        <a href="#chat-pesanan" class="block rounded-[14px] border-[1.5px] border-garis bg-white py-3.5 text-center text-[13px] font-bold text-arang hover:bg-kertas">Chat</a>
        <x-order-report :$order />
    </div>

    <div id="chat-pesanan" class="scroll-mt-24 md:col-start-2">
        <x-order-chat :$order :$messages />
    </div>

    <div class="kartu flex flex-col gap-2.5 p-[18px] md:col-start-1 md:p-6">
        <span class="text-sm font-bold text-arang">Detail &amp; biaya</span>
        @foreach ([
            'Model' => $order->model === 'panggilan' ? 'Panggilan ke rumah' : 'Datang ke tempat praktik',
            'Jadwal' => $order->scheduled_at->translatedFormat('l, d F Y · H:i'),
            'Alamat' => $order->address,
            'Catatan' => $order->notes,
        ] as $k => $v)
            @if ($v)
                <div class="flex justify-between gap-3.5">
                    <span class="shrink-0 text-[13px] font-medium text-kabut-muda">{{ $k }}</span>
                    <span class="text-right text-[13px] font-semibold leading-snug text-arang">{{ $v }}</span>
                </div>
            @endif
        @endforeach

        <span class="mt-1 h-px bg-garis-muda"></span>

        <div class="flex justify-between gap-3.5">
            <span class="text-[13px] font-medium text-kabut-muda">Harga layanan</span>
            <span class="text-[13px] font-semibold text-arang">Rp{{ number_format($order->price, 0, ',', '.') }}</span>
        </div>
        @if ($order->transport_fee > 0)
            <div class="flex justify-between gap-3.5">
                <span class="text-[13px] font-medium text-kabut-muda">Biaya transport</span>
                <span class="text-[13px] font-semibold text-arang">Rp{{ number_format($order->transport_fee, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="flex justify-between gap-3.5">
            <span class="text-[13px] font-medium text-kabut-muda">Biaya layanan</span>
            <span class="text-[13px] font-semibold text-arang">Rp{{ number_format($order->service_fee, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between gap-3.5 border-t border-garis-muda pt-2.5">
            <span class="font-display text-sm font-extrabold text-arang">Total</span>
            <span class="font-display text-base font-extrabold text-daun">Rp{{ number_format($order->total, 0, ',', '.') }}</span>
        </div>
    </div>

    @if (in_array($order->status, ['pending_confirmation', 'pending_payment', 'paid', 'accepted'], true))
        @php $sudahBayar = in_array($order->status, ['paid', 'accepted'], true); @endphp
        <div x-data="{ open: false }" class="flex flex-col gap-2.5 md:col-start-2">
            <button type="button" @click="open = ! open"
                    class="w-full rounded-[14px] border-[1.5px] border-jahe-garis bg-white py-4 text-[13px] font-bold text-jahe hover:bg-jahe-muda">Batalkan pesanan</button>
            <div x-show="open" x-cloak class="kartu flex flex-col gap-2.5 p-[18px]">
                @if ($sudahBayar)
                    <p class="text-[11px] font-medium leading-relaxed text-kabut-muda">
                        Dana dikembalikan dikurangi <span class="font-bold text-arang">biaya layanan Rp{{ number_format($order->service_fee, 0, ',', '.') }}</span> (menutup biaya pembayaran).
                        Membatalkan kurang dari {{ config('goterapis.cancel_free_hours') }} jam sebelum jadwal dikenai kompensasi terapis.
                    </p>
                @endif
                <form method="post" action="{{ route('pesanan.cancel', $order) }}" class="flex flex-col gap-2.5">
                    @csrf @method('patch')
                    <label>
                        <span class="sr-only">Alasan pembatalan</span>
                        <input name="cancel_reason" maxlength="255" placeholder="Alasan (opsional)" class="isian">
                    </label>
                    <button class="w-full rounded-[14px] bg-jahe py-3.5 text-[13px] font-bold text-white hover:bg-jahe-terang">Ya, batalkan pesanan</button>
                </form>
            </div>
        </div>
    @endif

    @if ($order->status === 'in_progress')
        <form method="post" action="{{ route('pesanan.complete', $order) }}"
              class="fixed inset-x-0 bottom-0 z-30 border-t border-garis bg-white px-5 pb-[max(1.75rem,env(safe-area-inset-bottom))] pt-3.5 md:static md:border-0 md:bg-transparent md:p-0">
            @csrf @method('patch')
            <button class="w-full rounded-2xl bg-arang py-4 text-[15px] font-bold text-white hover:bg-malam">Tandai layanan selesai</button>
        </form>
    @endif
</div>
@endsection
