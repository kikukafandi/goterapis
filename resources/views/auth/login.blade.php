@extends('layouts.bare')
@section('title', 'Masuk — GoTerapis')

@section('content')
<div class="grid min-h-dvh lg:h-dvh lg:min-h-0 lg:grid-cols-2 lg:overflow-hidden">

    {{-- Panel gambar desktop --}}
    <div class="relative hidden h-full overflow-hidden bg-daun lg:block">
        <img
            src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=70&w=1000&h=1400"
            alt="Terapi dengan minyak herbal"
            class="absolute inset-0 h-full w-full object-cover opacity-60"
        >

        <div class="absolute bottom-12 left-12 right-12 text-white">
            <p class="font-display text-[38px] font-extrabold leading-tight text-balance">
                Terapis tepercaya datang ke rumahmu.
            </p>
            <p class="mt-3 max-w-sm text-[15px] leading-relaxed text-white/85">
                Pijat, bekam, refleksi — sesuai jadwalmu. Bayar setelah pesanan diterima.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <div class="flex min-h-dvh items-center justify-center bg-white px-5 py-10 sm:px-6 lg:h-dvh lg:min-h-0 lg:overflow-y-auto lg:px-10">
        <div class="flex w-full max-w-sm flex-col">

            <a href="/" class="flex flex-col items-center gap-3.5">
                <x-logo variant="full" class="h-24" />
                <span class="text-center text-sm font-medium leading-relaxed text-kabut-muda text-pretty">
                    Terapis tepercaya datang ke rumahmu.<br>Pijat, bekam, refleksi — sesuai jadwalmu.
                </span>
            </a>

            <form method="post" action="/masuk" class="mt-9 flex flex-col gap-3">
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-xs font-semibold text-arang">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email" placeholder="nama@email.com" class="isian">
                </div>

                <div class="flex flex-col gap-1.5" x-data="{ show: false }">
                    <label for="password" class="text-xs font-semibold text-arang">Kata sandi</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required
                               autocomplete="current-password" placeholder="••••••••" class="isian pr-20">
                        <button type="button" @click="show = !show" x-text="show ? 'Sembunyi' : 'Lihat'"
                                class="absolute inset-y-0 right-3 text-xs font-bold text-daun hover:text-daun-tua"></button>
                    </div>
                </div>

                @if ($errors->any())
                    <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-jahe-garis bg-jahe-muda px-3.5 py-3">
                        <span class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-jahe-terang"></span>
                        <span class="text-xs font-medium leading-relaxed text-jahe">{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="flex cursor-pointer items-center gap-2.5 text-xs font-medium text-kabut">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-garis text-daun focus:ring-daun">
                    Ingat saya
                </label>

                <button type="submit" class="btn-utama mt-1.5 w-full text-[15px]">Masuk</button>
            </form>

            <div class="mt-10 flex flex-col items-center gap-4">
                <p class="text-[13px] font-medium text-kabut-muda">
                    Belum punya akun? <a href="/daftar" class="font-bold text-daun hover:text-daun-tua">Daftar</a>
                </p>
                <span class="h-px w-full bg-garis-muda"></span>
                <a href="/daftar-terapis" class="text-xs font-semibold text-kabut-muda hover:text-daun">Daftar sebagai terapis →</a>
            </div>
        </div>
    </div>
</div>
@endsection
