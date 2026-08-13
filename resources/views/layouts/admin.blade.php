<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101410">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo-mark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo-mark.png') }}">
    <title>@yield('title', 'Admin') — GoTerapis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-dvh bg-kertas-app antialiased" x-data="{ nav: false }">
@php
    // Angka antrean di sidebar — dipakai di semua layar admin.
    $antreanDokumen = \App\Models\TherapistDocument::where('status', 'pending')->count();
    $antreanPenarikan = \App\Models\Withdrawal::where('status', 'requested')->count();
    $antreanLaporan = \App\Models\Report::whereIn('status', ['open', 'reviewing'])->count();

    $menu = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Terapis', 'href' => route('admin.therapists'), 'active' => request()->routeIs('admin.therapist*'), 'count' => $antreanDokumen],
        ['label' => 'Penarikan', 'href' => route('admin.withdrawals.index'), 'active' => request()->routeIs('admin.withdrawals.*'), 'count' => $antreanPenarikan],
        ['label' => 'Transaksi', 'href' => route('admin.transactions.index'), 'active' => request()->routeIs('admin.transactions.*')],
        ['label' => 'Laporan', 'href' => route('admin.reports.index'), 'active' => request()->routeIs('admin.reports.*'), 'count' => $antreanLaporan],
        ['label' => 'Artikel', 'href' => route('admin.articles.index'), 'active' => request()->routeIs('admin.articles.*')],
        ['label' => 'Produk', 'href' => route('admin.products.index'), 'active' => request()->routeIs('admin.products.*')],
        ['label' => 'Banner promosi', 'href' => route('admin.banners.index'), 'active' => request()->routeIs('admin.banners.*')],
        ['label' => 'WhatsApp Gateway', 'href' => route('admin.whatsapp'), 'active' => request()->routeIs('admin.whatsapp')],
    ];
@endphp

{{-- Sidebar gelap --}}
<aside class="fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col gap-7 overflow-y-auto bg-malam p-4 transition-transform lg:translate-x-0"
       :class="nav && 'translate-x-0'">
    <div class="flex items-center gap-3 px-1.5 pt-1">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white p-1">
                <x-logo class="h-full" />
            </span>
            <span class="flex flex-col gap-1">
                <span class="font-display text-sm font-extrabold text-white">GoTerapis</span>
                <span class="text-[10px] font-semibold tracking-[.06em] text-daun-neon">ADMIN</span>
            </span>
        </a>
        <button @click="nav = false" class="ml-auto grid h-9 w-9 place-items-center rounded-full text-white/60 hover:bg-white/10 lg:hidden" aria-label="Tutup menu">
            <x-icon name="close" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex flex-col gap-0.5">
        @foreach ($menu as $m)
            <a href="{{ $m['href'] }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold {{ $m['active'] ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5' }}">
                <span class="h-[7px] w-[7px] shrink-0 rounded-full {{ $m['active'] ? 'bg-daun-neon' : 'bg-white/20' }}"></span>
                <span class="flex-1">{{ $m['label'] }}</span>
                @if (($m['count'] ?? 0) > 0)
                    <span class="min-w-5 rounded-full bg-jahe-terang px-1.5 text-center text-[10px] font-bold leading-5 text-white">{{ $m['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="mt-auto flex items-center gap-3 rounded-2xl bg-white/5 p-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/10 text-xs font-bold text-daun-neon">
            {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
        </span>
        <span class="flex min-w-0 flex-1 flex-col gap-1">
            <span class="truncate text-xs font-bold text-white">{{ auth()->user()->name }}</span>
            <span class="truncate text-[10px] font-medium text-white/45">{{ auth()->user()->email }}</span>
        </span>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="grid h-8 w-8 place-items-center rounded-lg text-white/50 hover:bg-white/10 hover:text-white" aria-label="Keluar" title="Keluar">
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>
    </div>
</aside>
<div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-arang/50 lg:hidden"></div>

{{-- Konten --}}
<div class="lg:pl-60">
    <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-garis bg-kertas-app/95 px-4 py-4 backdrop-blur sm:px-8">
        <button @click="nav = true" class="grid h-10 w-10 shrink-0 place-items-center rounded-full hover:bg-white lg:hidden" aria-label="Buka menu">
            <x-icon name="menu" class="h-6 w-6 text-arang" />
        </button>
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <h1 class="font-display truncate text-xl font-extrabold text-arang sm:text-[22px]">@yield('heading', 'Admin')</h1>
            <p class="truncate text-xs font-medium text-kabut-muda">@yield('subheading', 'Panel pengelola GoTerapis')</p>
        </div>
        <a href="{{ route('notifications.index') }}" aria-label="Notifikasi" class="relative grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-garis bg-white text-arang hover:bg-kertas">
            <x-icon name="bell" class="h-5 w-5" />
            @if ($unread = auth()->user()->unreadNotifications()->count())
                <span class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-jahe-terang px-1 text-center text-[10px] font-bold leading-[18px] text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
            @endif
        </a>
        <span class="hidden shrink-0 items-center gap-2.5 rounded-xl border border-garis bg-white px-3.5 py-3 md:flex">
            <span class="denyut h-[7px] w-[7px] rounded-full bg-daun-terang"></span>
            <span class="text-[11px] font-semibold text-kabut">Gateway Midtrans aktif</span>
        </span>
    </header>

    <x-notifications />

    <main class="mx-auto w-full max-w-[1300px] px-4 py-7 sm:px-8 sm:pb-10">
        <x-flash class="mb-5" />
        @yield('content')
    </main>
</div>
</body>
</html>
