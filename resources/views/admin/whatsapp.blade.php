@extends('layouts.admin')
@section('title', 'WhatsApp')
@section('heading', 'WhatsApp')
@section('content')
@php
$status = $gateway['status'];
$ready = $status === 'ready';
$labels = [
    'ready' => 'Terhubung',
    'qr' => 'Menunggu pemindaian',
    'authenticated' => 'Menyiapkan sesi',
    'starting' => 'Memulai gateway',
    'disconnected' => 'Terputus',
    'auth_failure' => 'Autentikasi gagal',
    'unavailable' => 'Gateway tidak tersedia',
    'disabled' => 'Belum dikonfigurasi',
];
@endphp
<header class="mb-6 border-b border-garis pb-5"><h2 class="font-display text-2xl font-bold text-arang">Koneksi WhatsApp</h2><p class="mt-1.5 text-sm leading-6 text-kabut">Pantau sesi pengiriman notifikasi operasional GoTerapis.</p></header>
<div class="grid gap-5 lg:grid-cols-[1.2fr_.8fr]">
    <section class="rounded-card border border-garis bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-kabut">Kanal operasional</p>
                <h2 class="mt-2 font-display text-2xl font-bold text-arang">Notifikasi WhatsApp</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-kabut">Hubungkan nomor khusus GoTerapis untuk mengirim kabar pesanan penting kepada pelanggan dan terapis.</p>
            </div>
            <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold {{ $ready ? 'bg-daun-muda text-daun-tua' : (in_array($status, ['unavailable', 'auth_failure', 'disconnected']) ? 'bg-jahe/10 text-jahe' : 'bg-kunyit-muda text-arang') }}">{{ $labels[$status] ?? ucfirst($status) }}</span>
        </div>

        @if ($ready)
            <div class="mt-8 rounded-card bg-daun-tua p-5 text-white">
                <div class="flex items-center gap-4">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="phone" class="h-6 w-6" /></span>
                    <div><p class="text-xs text-white/65">Nomor terhubung</p><p class="mt-1 text-lg font-bold">+{{ $gateway['phone'] ?? 'Nomor WhatsApp' }}</p></div>
                </div>
            </div>
        @elseif ($status === 'qr' && isset($gateway['qr']))
            <div class="mt-7 flex flex-col items-center rounded-card border border-garis bg-kertas p-6 text-center">
                <img src="{{ $gateway['qr'] }}" alt="Kode QR untuk menautkan WhatsApp" class="aspect-square h-auto w-64 max-w-full rounded-xl bg-white p-3">
                <h3 class="mt-5 font-display text-lg font-bold text-arang">Pindai dari WhatsApp</h3>
                <p class="mt-2 max-w-sm text-sm text-kabut">Buka Perangkat tertaut di WhatsApp pada ponsel operasional, lalu pindai kode ini.</p>
            </div>
        @else
            <div class="mt-7 rounded-card border border-kunyit/40 bg-kunyit/10 p-5 text-sm text-arang">
                @if ($status === 'disabled')
                    Isi <code class="font-bold">WHATSAPP_GATEWAY_TOKEN</code>, jalankan gateway, lalu muat ulang halaman ini.
                @else
                    {{ $gateway['error'] ?? 'Gateway sedang menyiapkan sesi. Muat ulang beberapa saat lagi.' }}
                @endif
            </div>
        @endif

        @if (! $ready)<p aria-live="polite" class="mt-4 flex items-center gap-2 text-xs text-kabut"><span class="h-2 w-2 animate-pulse rounded-full bg-kunyit"></span>Status diperbarui otomatis setiap 3 detik.</p>@endif
        <a href="{{ route('admin.whatsapp') }}" class="mt-5 inline-flex min-h-10 items-center gap-2 rounded-full border border-garis px-4 text-sm font-bold text-arang hover:border-daun hover:text-daun"><x-icon name="arrow-right" class="h-4 w-4" /> Muat ulang status</a>
    </section>

    <aside class="rounded-card border border-garis bg-daun-muda p-6">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-daun">Cara kerja</p>
        <ol class="mt-5 space-y-5 text-sm text-arang">
            <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-daun text-xs font-bold text-white">1</span><p>Jalankan layanan gateway WhatsApp di server.</p></li>
            <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-daun text-xs font-bold text-white">2</span><p>Pindai QR sekali memakai nomor operasional.</p></li>
            <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-daun text-xs font-bold text-white">3</span><p>Pesanan baru dan perubahan status dikirim otomatis.</p></li>
        </ol>
        <p class="mt-6 border-t border-daun/15 pt-5 text-xs leading-5 text-kabut">Layanan ini memakai WhatsApp Web tidak resmi. Hindari pesan pemasaran massal dan gunakan nomor khusus operasional.</p>
    </aside>
</div>
@if (! $ready)
<script>setTimeout(() => window.location.reload(), 3000)</script>
@endif
@endsection
