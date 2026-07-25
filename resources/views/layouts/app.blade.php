<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2e5a39">
    <title>@yield('title', 'GoTerapis') — Temukan Terapis di Sekitarmu</title>
    <meta name="description" content="@yield('meta', 'Platform komunitas & pemesanan terapis pijat, bekam, kretek, dan refleksi. Cari terapis terpercaya di sekitarmu, atur jadwal, bayar aman.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="min-h-dvh antialiased">
    @include('partials.header')

    <main class="pb-24 md:pb-0">
        @if (session('success'))
            <div class="mx-auto max-w-6xl px-4 pt-4">
                <div class="flex items-start gap-2 rounded-xl border border-daun/25 bg-daun-muda px-4 py-3 text-sm text-daun-tua">
                    <x-icon name="shield" class="mt-0.5 h-5 w-5 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4">
                <div class="flex items-start gap-2 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe">
                    <x-icon name="shield" class="mt-0.5 h-5 w-5 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.bottom-nav')

    @stack('scripts')
</body>
</html>
