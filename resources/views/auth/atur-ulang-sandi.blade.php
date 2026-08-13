@extends('layouts.bare')
@section('title', 'Kata sandi baru — GoTerapis')

@section('content')
<div class="flex min-h-dvh items-center justify-center bg-white px-5 py-10 sm:px-6">
    <div class="flex w-full max-w-sm flex-col">

        <a href="/" class="flex flex-col items-center gap-3.5">
            <x-logo variant="full" class="h-24" />
        </a>

        <h1 class="mt-9 text-center font-display text-2xl font-extrabold text-arang">Kata sandi baru</h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-kabut-muda text-pretty">
            Buat kata sandi baru untuk akunmu.
        </p>

        <form method="post" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-semibold text-arang">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                       autocomplete="email" placeholder="nama@email.com" class="isian">
            </div>

            <div class="flex flex-col gap-1.5" x-data="{ show: false }">
                <label for="password" class="text-xs font-semibold text-arang">Kata sandi baru</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autofocus
                           autocomplete="new-password" placeholder="••••••••" class="isian pr-20">
                    <button type="button" @click="show = !show" x-text="show ? 'Sembunyi' : 'Lihat'"
                            class="absolute inset-y-0 right-3 text-xs font-bold text-daun hover:text-daun-tua"></button>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-xs font-semibold text-arang">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" placeholder="••••••••" class="isian">
            </div>

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-jahe-garis bg-jahe-muda px-3.5 py-3">
                    <span class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-jahe-terang"></span>
                    <span class="text-xs font-medium leading-relaxed text-jahe">{{ $errors->first() }}</span>
                </div>
            @endif

            <button type="submit" class="btn-utama mt-1.5 w-full text-[15px]">Simpan kata sandi</button>
        </form>
    </div>
</div>
@endsection
