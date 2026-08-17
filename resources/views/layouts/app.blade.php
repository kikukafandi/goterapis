<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4fbf17">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo-mark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo-mark.png') }}">
    @php
        $seoTitle = trim($__env->yieldContent('title', \App\Models\Setting::get('seo_title')));
        $seoTitle = $seoTitle === 'GoTerapis' || str_contains($seoTitle, 'GoTerapis') ? $seoTitle : $seoTitle.' — GoTerapis';
        $seoDescription = trim($__env->yieldContent('meta', \App\Models\Setting::get('seo_description')));
        $seoCanonical = trim($__env->yieldContent('canonical', url()->current()));
        $seoImage = trim($__env->yieldContent('image', \App\Models\Setting::imageUrl('seo_image', asset('images/brand/logo-mark.png'))));
        $seoRobots = trim($__env->yieldContent('robots', request()->routeIs('cari', 'akun', 'tutorial', 'notifications.*', 'pesan.*', 'pesanan.*', 'chat', 'mitra.*') || request()->query() ? 'noindex, nofollow' : 'index, follow'));
        $seoType = trim($__env->yieldContent('type', 'website'));
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="GoTerapis">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    @if (request()->routeIs('home'))
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [['@type' => 'Organization', 'name' => 'GoTerapis', 'url' => route('home'), 'logo' => asset('images/brand/logo-mark.png')], ['@type' => 'WebSite', 'name' => 'GoTerapis', 'url' => route('home'), 'inLanguage' => 'id-ID']]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="min-h-dvh antialiased">
    @php($isTherapistShell = auth()->user()?->isTherapist() && request()->routeIs('mitra.*', 'chat', 'notifications.*'))
    @include('partials.header')
    <x-notifications />

    <main class="pb-24 md:pb-0">
        <x-flash class="mx-auto mt-4 w-full max-w-[1300px] px-4 sm:px-6" />
        @yield('content')
    </main>

    @unless ($isTherapistShell)
        @include('partials.footer')
    @endunless
    @include('partials.bottom-nav')

    @stack('scripts')
</body>
</html>
