@extends('layouts.app')
@section('title', 'Pesanan '.$order->code)

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
    $t = $order->therapistProfile;

    // Langkah alur pesanan (Shopee-style). Terapis konfirmasi dulu, baru pengguna bayar.
    $steps = [
        ['label' => 'Pesanan dibuat', 'icon' => 'clipboard', 'at' => $order->created_at],
        ['label' => 'Diterima terapis', 'icon' => 'shield', 'at' => $order->accepted_at],
        ['label' => 'Pembayaran', 'icon' => 'wallet', 'at' => $order->payment?->paid_at],
        ['label' => 'Layanan berlangsung', 'icon' => 'leaf', 'at' => $order->started_at],
        ['label' => 'Selesai', 'icon' => 'star', 'at' => $order->completed_at],
    ];
    $currentStep = match ($order->status) {
        'pending_payment' => 1, 'paid', 'accepted' => 2, 'in_progress' => 3, 'completed' => 4, default => 0,
    };
    $negatives = ['rejected', 'cancelled', 'refunded', 'disputed'];
    $isNegative = in_array($order->status, $negatives, true);

    $hints = [
        'pending_confirmation' => 'Menunggu terapis mengonfirmasi. Kamu baru membayar setelah pesanan diterima.',
        'pending_payment' => 'Terapis sudah menerima. Selesaikan pembayaran — dana ditahan platform sampai layanan selesai.',
        'paid' => 'Pembayaran diterima. Siapkan dirimu sesuai jadwal.',
        'accepted' => 'Terapis sudah menerima. Siapkan dirimu sesuai jadwal.',
        'in_progress' => 'Layanan sedang berlangsung.',
        'completed' => 'Layanan selesai. Terima kasih! Beri ulasan untuk terapis.',
        'rejected' => 'Terapis tidak dapat menerima pesanan ini. Kamu bisa mencari terapis lain.',
        'cancelled' => 'Pesanan ini dibatalkan. Buat pesanan baru jika masih membutuhkan layanan.',
        'refunded' => 'Pengembalian dana telah diproses.',
        'disputed' => 'Pesanan sedang ditinjau admin. Pantau halaman ini untuk pembaruan.',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6">
    <a href="{{ route('pesanan.index') }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">← Riwayat pesanan</a>

    {{-- Status utama --}}
    <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs text-kabut">Nomor pesanan</p>
                <p class="font-display text-xl font-bold text-arang">{{ $order->code }}</p>
            </div>
            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $isNegative ? 'bg-jahe/15 text-jahe' : 'bg-kunyit/15 text-arang' }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
        </div>

        {{-- Info langkah berikutnya --}}
        <div class="mt-4 rounded-xl border px-4 py-3 text-sm {{ $isNegative ? 'border-jahe/30 bg-jahe/10 text-jahe' : 'border-daun/25 bg-daun-muda text-daun-tua' }}">
            {{ $hints[$order->status] ?? '' }}
            @if ($isNegative && $order->cancel_reason)
                <span class="mt-1 block text-xs">Alasan: {{ $order->cancel_reason }}</span>
            @endif
        </div>

        @if ($order->status === 'pending_payment' && ! $order->paymentExpired())
            <form method="post" action="{{ route('pesanan.pay', $order) }}" class="mt-4">
                @csrf
                <button class="w-full rounded-full bg-daun px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">
                    Bayar sekarang · Rp{{ number_format($order->total, 0, ',', '.') }}
                </button>
                @if ($batasBayar = $order->paymentDeadline())
                    <p class="mt-2 text-center text-xs text-kabut">
                        Bayar sebelum {{ $batasBayar->translatedFormat('d M Y · H:i') }} — lewat itu pesanan batal otomatis.
                    </p>
                @endif
            </form>
        @elseif (in_array($order->status, ['paid', 'accepted'], true))
            <div class="mt-4 rounded-xl border border-daun/25 bg-white p-4 text-center">
                <p class="text-xs text-kabut">PIN mulai layanan — berikan ke terapis saat mulai</p>
                <p class="mt-1 font-display text-3xl font-bold tracking-[0.3em] text-daun">{{ $order->start_pin }}</p>
            </div>
        @elseif ($order->status === 'in_progress')
            <form method="post" action="{{ route('pesanan.complete', $order) }}" class="mt-4">
                @csrf @method('patch')
                <button class="w-full rounded-full bg-daun px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua">
                    Konfirmasi layanan selesai
                </button>
            </form>
        @elseif ($order->status === 'completed')
            @if ($order->review)
                <div class="mt-4 rounded-xl border border-garis bg-white p-4">
                    <p class="text-xs text-kabut">Ulasanmu</p>
                    <div class="mt-1 flex items-center gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 {{ $i <= round($order->review->averageRating()) ? 'text-kunyit' : 'text-garis' }}"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                        @endfor
                    </div>
                    @if ($order->review->body)<p class="mt-2 text-sm text-arang">{{ $order->review->body }}</p>@endif
                </div>
            @else
                <form method="post" action="{{ route('pesanan.review', $order) }}" class="mt-4 rounded-xl border border-garis bg-white p-4"
                      x-data="{ rating: 0, hover: 0 }">
                    @csrf
                    <p class="text-sm font-semibold text-arang">Beri ulasan</p>
                    <p class="mt-0.5 text-xs text-kabut">Seberapa puas kamu dengan layanan ini?</p>
                    <input type="hidden" name="rating" :value="rating">
                    <div class="mt-2 flex items-center gap-1" @mouseleave="hover = 0">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}"
                                    class="transition-transform hover:scale-110"
                                    :class="{{ $i }} <= (hover || rating) ? 'text-kunyit' : 'text-garis'">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                            </button>
                        @endfor
                    </div>
                    <textarea name="body" rows="3" placeholder="Ceritakan pengalamanmu (opsional)…"
                              class="mt-3 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none placeholder:text-kabut focus:border-daun"></textarea>
                    <button type="submit" x-bind:disabled="rating === 0"
                            class="mt-3 w-full rounded-full bg-daun px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua disabled:opacity-50">
                        Kirim ulasan
                    </button>
                </form>
            @endif
        @endif

        {{-- Pembatalan (sebelum layanan berjalan) --}}
        @if (in_array($order->status, ['pending_confirmation', 'pending_payment', 'paid', 'accepted'], true))
            @php $sudahBayar = in_array($order->status, ['paid', 'accepted'], true); @endphp
            <div class="mt-4 border-t border-garis pt-4" x-data="{ open: false }">
                <button type="button" @click="open = ! open" class="text-sm font-semibold text-kabut hover:text-jahe">Batalkan pesanan</button>
                <div x-show="open" x-cloak class="mt-3">
                    @if ($sudahBayar)
                        <p class="text-xs text-kabut">
                            Dana dikembalikan dikurangi <span class="font-semibold text-arang">biaya layanan Rp{{ number_format($order->service_fee, 0, ',', '.') }}</span> (menutup biaya pembayaran).
                            Membatalkan kurang dari {{ config('goterapis.cancel_free_hours') }} jam sebelum jadwal dikenai kompensasi terapis.
                        </p>
                    @endif
                    <form method="post" action="{{ route('pesanan.cancel', $order) }}" class="mt-2">
                        @csrf @method('patch')
                        <input name="cancel_reason" maxlength="255" placeholder="Alasan (opsional)"
                               class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none placeholder:text-kabut focus:border-daun">
                        <button class="mt-2 w-full rounded-full border border-jahe/40 px-4 py-2.5 text-sm font-semibold text-jahe transition-colors hover:bg-jahe/10">
                            Ya, batalkan pesanan
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Timeline progres --}}
        @unless ($isNegative)
            <ol class="mt-6 space-y-5">
                @foreach ($steps as $i => $s)
                    @php $done = $i <= $currentStep; $active = $i === $currentStep; @endphp
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $done ? 'bg-daun text-white' : 'bg-kertas text-kabut ring-1 ring-garis' }}">
                                <x-icon :name="$s['icon']" class="h-4 w-4" />
                            </span>
                            @unless ($loop->last)
                                <span class="mt-1 w-0.5 flex-1 {{ $i < $currentStep ? 'bg-daun' : 'bg-garis' }}" style="min-height:1.25rem"></span>
                            @endunless
                        </div>
                        <div class="pb-1 pt-1">
                            <p class="text-sm font-semibold {{ $done ? 'text-arang' : 'text-kabut' }}">
                                {{ $s['label'] }}
                                @if ($active)<span class="ml-1 text-xs font-normal text-daun">• saat ini</span>@endif
                            </p>
                            @if ($s['at'])
                                <p class="text-xs text-kabut">{{ $s['at']->translatedFormat('d M Y · H:i') }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endunless
    </div>

    <div class="mt-5">
        <x-order-chat :$order :$messages />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        {{-- Detail pesanan --}}
        <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-arang">Detail pesanan</h2>
            <dl class="mt-3 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-kabut">Terapis</dt><dd class="text-right font-semibold text-arang">{{ $t->user->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-kabut">Layanan</dt><dd class="text-right font-semibold text-arang">{{ $order->service->name }} · {{ $order->duration_min }} menit</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-kabut">Model</dt><dd class="text-right font-semibold text-arang">{{ $order->model === 'panggilan' ? 'Panggilan ke rumah' : 'Datang ke tempat praktik' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-kabut">Jadwal</dt><dd class="text-right font-semibold text-arang">{{ $order->scheduled_at->translatedFormat('l, d F Y · H:i') }}</dd></div>
                @if ($order->address)
                    <div class="flex justify-between gap-4"><dt class="text-kabut">Alamat</dt><dd class="text-right font-semibold text-arang">{{ $order->address }}</dd></div>
                @endif
                @if ($order->notes)
                    <div class="flex justify-between gap-4"><dt class="text-kabut">Catatan</dt><dd class="text-right text-arang">{{ $order->notes }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Rincian biaya --}}
        <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-arang">Rincian biaya</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-kabut">Harga layanan</dt><dd class="font-semibold text-arang">Rp{{ number_format($order->price, 0, ',', '.') }}</dd></div>
                @if ($order->transport_fee > 0)
                    <div class="flex justify-between"><dt class="text-kabut">Biaya transport</dt><dd class="font-semibold text-arang">Rp{{ number_format($order->transport_fee, 0, ',', '.') }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-kabut">Biaya layanan</dt><dd class="font-semibold text-arang">Rp{{ number_format($order->service_fee, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between border-t border-garis pt-2"><dt class="font-bold text-arang">Total</dt><dd class="font-bold text-daun">Rp{{ number_format($order->total, 0, ',', '.') }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
