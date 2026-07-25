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
        ['label' => 'Artikel', 'href' => route('admin.articles.index'), 'icon' => 'clipboard', 'active' => request()->routeIs('admin.articles.*')],
    ];
@endphp

{{-- Sidebar --}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-garis bg-white transition-transform lg:translate-x-0"
       :class="nav && 'translate-x-0'">
    <div class="flex items-center justify-between px-5 py-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-display text-xl font-semibold text-daun">
            <x-icon name="leaf" class="h-6 w-6" /> GoTerapis
        </a>
        <button @click="nav = false" class="lg:hidden" aria-label="Tutup"><x-icon name="close" class="h-5 w-5 text-kabut" /></button>
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
        <button @click="nav = true" class="lg:hidden" aria-label="Menu"><x-icon name="menu" class="h-6 w-6 text-arang" /></button>
        <h1 class="font-display text-lg font-semibold text-arang">@yield('heading', 'Admin')</h1>
        <div class="ml-auto flex items-center gap-3">
            <span class="hidden text-sm text-kabut sm:block">{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-full border border-garis px-3 py-1.5 text-sm font-semibold text-arang hover:bg-kertas">Keluar</button>
            </form>
        </div>
    </header>

    @if (session('ok'))
        <div class="mx-4 mt-4 rounded-xl border border-daun/20 bg-daun-muda px-4 py-3 text-sm font-semibold text-daun-tua">
            {{ session('ok') }}
        </div>
    @endif

    <main class="p-4 sm:p-6">
        @yield('content')
    </main>
</div>
</body>
</html>
