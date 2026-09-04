@extends('layouts.bare')

@section('title', trim($__env->yieldContent('code')).' — GoTerapis')

@section('content')
<main class="grid min-h-dvh place-items-center bg-kertas px-5 py-12">
    <section class="w-full max-w-xl overflow-hidden rounded-card border border-garis bg-white">
        <div class="bg-malam px-6 py-8 text-white sm:px-10 sm:py-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="Kembali ke GoTerapis">
                <span class="grid h-11 w-11 place-items-center rounded-[13px] bg-white p-1.5"><x-logo class="h-full" /></span>
                <span class="font-display text-lg font-extrabold">GoTerapis</span>
            </a>
            <p class="mt-10 font-serif text-sm font-semibold text-daun-muda">Kesalahan @yield('code')</p>
            <h1 class="font-display mt-2 text-3xl font-extrabold leading-tight sm:text-4xl">@yield('heading')</h1>
        </div>
        <div class="px-6 py-7 sm:px-10 sm:py-9">
            <p class="max-w-md text-sm font-medium leading-7 text-kabut">@yield('message')</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="btn-utama">Ke beranda</a>
                <button type="button" onclick="history.back()" class="btn-garis">Kembali</button>
            </div>
        </div>
    </section>
</main>
@endsection
