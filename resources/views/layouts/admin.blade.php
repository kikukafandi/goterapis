<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2e5a39">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — GoTerapis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-dvh bg-kertas antialiased" x-data="{ nav: false }">
@php
    $menu = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home', 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Verifikasi Terapis', 'href' => route('admin.therapists'), 'icon' => 'shield', 'active' => request()->routeIs('admin.therapist*')],
        ['label' => 'Penarikan', 'href' => route('admin.withdrawals.index'), 'icon' => 'wallet', 'active' => request()->routeIs('admin.withdrawals.*')],
        ['label' => 'Artikel', 'href' => route('admin.articles.index'), 'icon' => 'clipboard', 'active' => request()->routeIs('admin.articles.*')],
        ['label' => 'Produk', 'href' => route('admin.products.index'), 'icon' => 'leaf', 'active' => request()->routeIs('admin.products.*')],
        ['label' => 'Banner Promosi', 'href' => route('admin.banners.index'), 'icon' => 'image', 'active' => request()->routeIs('admin.banners.*')],
        ['label' => 'WhatsApp', 'href' => route('admin.whatsapp'), 'icon' => 'phone', 'active' => request()->routeIs('admin.whatsapp')],
    ];
@endphp

{{-- Sidebar --}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto border-r border-garis bg-white transition-transform lg:translate-x-0"
       :class="nav && 'translate-x-0'">
    <div class="flex items-center justify-between px-5 py-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-display text-xl font-semibold text-daun">
            <span class="grid h-7 w-7 place-items-center rounded-lg border border-daun text-[10px] font-bold">GT</span> GoTerapis
        </a>
        <button @click="nav = false" class="grid h-10 w-10 place-items-center rounded-full hover:bg-kertas lg:hidden" aria-label="Tutup menu"><x-icon name="close" class="h-5 w-5 text-kabut" /></button>
    </div>
    <p class="px-5 pb-2 pt-3 text-xs font-bold uppercase tracking-wide text-kabut">Admin</p>
    <nav class="space-y-1 px-3">
        @foreach ($menu as $m)
            <a href="{{ $m['href'] }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ $m['active'] ? 'bg-daun text-white' : 'text-arang hover:bg-kertas' }}">
                <x-icon :name="$m['icon']" class="h-5 w-5" /> {{ $m['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
<div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-arang/40 lg:hidden"></div>

{{-- Konten --}}
<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-garis bg-white/95 px-4 py-3 backdrop-blur">
        <button @click="nav = true" class="grid h-10 w-10 place-items-center rounded-full hover:bg-kertas lg:hidden" aria-label="Buka menu"><x-icon name="menu" class="h-6 w-6 text-arang" /></button>
        <h1 class="font-display text-lg font-semibold text-arang">@yield('heading', 'Admin')</h1>
        <div class="ml-auto flex items-center gap-3">
            <a href="{{ route('notifications.index') }}" aria-label="Notifikasi" class="relative grid h-10 w-10 place-items-center rounded-full text-arang hover:bg-kertas">
                <x-icon name="bell" class="h-5 w-5" />
                @if ($unread = auth()->user()->unreadNotifications()->count())
                    <span class="absolute right-0 top-0 min-w-5 rounded-full bg-kunyit px-1 text-center text-[10px] font-bold leading-5 text-arang">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
            </a>
            <span class="hidden text-sm text-kabut sm:block">{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-full border border-garis px-3 py-1.5 text-sm font-semibold text-arang hover:bg-kertas">Keluar</button>
            </form>
        </div>
    </header>

    <div class="mx-auto w-full max-w-[1500px] px-4 sm:px-6">
        @if (session('ok') || session('success'))
            <div role="status" class="mt-4 flex items-center gap-3 rounded-xl border border-daun/20 bg-daun-muda px-4 py-3 text-sm font-semibold text-daun-tua"><x-icon name="shield" class="h-5 w-5" /> {{ session('ok') ?? session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="mt-4 flex items-center gap-3 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm font-semibold text-jahe"><x-icon name="shield" class="h-5 w-5" /> {{ session('error') }}</div>
        @endif
    </div>

    <main class="mx-auto w-full max-w-[1500px] p-4 sm:p-6">
        @yield('content')
    </main>
</div>
</body>
</html>
