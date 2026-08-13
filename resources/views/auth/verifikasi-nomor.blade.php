@extends('layouts.bare')
@section('title', 'Verifikasi nomor WhatsApp — GoTerapis')

@section('content')
<div class="flex min-h-dvh items-center justify-center bg-white px-5 py-10 sm:px-6">
    <div class="flex w-full max-w-sm flex-col">

        <a href="/" class="flex flex-col items-center gap-3.5">
            <x-logo variant="full" class="h-24" />
        </a>

        <h1 class="mt-9 text-center font-display text-2xl font-extrabold text-arang">Verifikasi nomor WhatsApp</h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-kabut-muda text-pretty">
            Kami kirim kode 6 digit lewat WhatsApp. Nomor ini dipakai terapis untuk menghubungimu saat pesanan berjalan.
        </p>

        <x-flash class="mt-6" />

        {{-- Nomor bisa diperbaiki di sini kalau salah ketik saat daftar. --}}
        <form method="post" action="{{ route('phone.send') }}" class="mt-6 flex flex-col gap-3">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-xs font-semibold text-arang">Nomor WhatsApp</label>
                <div class="flex gap-2">
                    <input id="phone" name="phone" type="tel" inputmode="tel" required
                           value="{{ old('phone', auth()->user()->phone) }}" placeholder="08xxxxxxxxxx" class="isian">
                    <button type="submit" class="btn-garis shrink-0 text-xs">Kirim kode</button>
                </div>
                @error('phone')<p class="text-xs font-medium text-jahe">{{ $message }}</p>@enderror
            </div>
        </form>

        <form method="post" action="{{ route('phone.confirm') }}" class="mt-3 flex flex-col gap-3">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="code" class="text-xs font-semibold text-arang">Kode verifikasi</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required
                       placeholder="6 digit" class="isian text-center text-lg font-bold tracking-[0.4em]">
                @error('code')<p class="text-xs font-medium text-jahe">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-utama w-full text-[15px]">Verifikasi</button>
        </form>

        <p class="mt-10 text-center text-[13px] font-medium text-kabut-muda">
            Belum sempat? <a href="/" class="font-bold text-daun hover:text-daun-tua">Nanti saja</a> — nomor tetap harus terverifikasi sebelum memesan.
        </p>
    </div>
</div>
@endsection
