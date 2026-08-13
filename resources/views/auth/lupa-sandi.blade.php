@extends('layouts.bare')
@section('title', 'Lupa kata sandi — GoTerapis')

@section('content')
<div class="flex min-h-dvh items-center justify-center bg-white px-5 py-10 sm:px-6">
    <div class="flex w-full max-w-sm flex-col">

        <a href="/" class="flex flex-col items-center gap-3.5">
            <x-logo variant="full" class="h-24" />
        </a>

        <h1 class="mt-9 text-center font-display text-2xl font-extrabold text-arang">Lupa kata sandi</h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-kabut-muda text-pretty">
            Masukkan email akunmu. Kami kirimkan tautan untuk membuat kata sandi baru.
        </p>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-xl border border-daun-garis bg-daun-muda px-3.5 py-3 text-xs font-medium leading-relaxed text-daun-tua">
                {{ session('status') }}
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-3">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-semibold text-arang">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       autocomplete="email" placeholder="nama@email.com" class="isian">
            </div>

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-jahe-garis bg-jahe-muda px-3.5 py-3">
                    <span class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-jahe-terang"></span>
                    <span class="text-xs font-medium leading-relaxed text-jahe">{{ $errors->first() }}</span>
                </div>
            @endif

            <button type="submit" class="btn-utama mt-1.5 w-full text-[15px]">Kirim tautan</button>
        </form>

        <p class="mt-10 text-center text-[13px] font-medium text-kabut-muda">
            Ingat kata sandimu? <a href="/masuk" class="font-bold text-daun hover:text-daun-tua">Masuk</a>
        </p>
    </div>
</div>
@endsection
