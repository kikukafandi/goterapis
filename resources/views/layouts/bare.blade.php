<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4fbf17">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo-mark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo-mark.png') }}">
    <title>@yield('title', 'GoTerapis')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-dvh antialiased">
    @yield('content')
</body>
</html>
