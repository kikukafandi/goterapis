@extends('layouts.bare')
@section('title', 'Masuk — GoTerapis')

@section('content')
<div class="grid min-h-dvh lg:h-dvh lg:min-h-0 lg:grid-cols-2 lg:overflow-hidden">

    {{-- Panel gambar desktop --}}
    <div class="relative hidden h-full overflow-hidden lg:block">
        <img
            src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=70&w=1000&h=1400"
            alt="Terapi dengan minyak herbal"
            class="absolute inset-0 h-full w-full object-cover"
        >

        <div class="absolute inset-0 bg-daun/45"></div>

        <div class="absolute bottom-10 left-10 right-10 text-white">
            <p class="font-display text-3xl font-semibold">
                Lanjutkan pesananmu dengan mudah.
            </p>
            <p class="mt-2 max-w-sm text-white/85">
                Terpercaya, transparan, dan tercatat.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <div class="flex min-h-dvh items-center justify-center px-4 py-8 sm:px-6 lg:h-dvh lg:min-h-0 lg:overflow-hidden lg:px-10 lg:py-6">
        <div class="w-full max-w-sm">

            <a
                href="/"
                class="mb-6 flex items-center gap-2 font-display text-2xl font-semibold text-daun"
            >
                <x-icon name="leaf" class="h-7 w-7" />
                GoTerapis
            </a>

            <h1 class="font-display text-2xl font-semibold text-arang">
                Masuk ke akunmu
            </h1>

            <p class="mt-1 text-sm text-kabut">
                Belum punya akun?
                <a
                    href="/daftar"
                    class="font-semibold text-daun hover:underline"
                >
                    Daftar
                </a>
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="/masuk" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-arang">
                        Email
                    </label>

                    <div class="flex items-center gap-2 rounded-xl border border-garis bg-white px-3 py-2.5 transition-colors focus-within:border-daun">
                        <x-icon name="user" class="h-5 w-5 shrink-0 text-kabut" />

                        <input
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            class="min-w-0 flex-1 bg-transparent text-sm text-arang outline-none"
                            placeholder="nama@email.com"
                        >
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <label class="mb-1.5 block text-sm font-semibold text-arang">
                        Kata sandi
                    </label>

                    <div class="flex items-center gap-2 rounded-xl border border-garis bg-white px-3 py-2.5 transition-colors focus-within:border-daun">
                        <x-icon name="shield" class="h-5 w-5 shrink-0 text-kabut" />

                        <input
                            name="password"
                            :type="show ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            class="min-w-0 flex-1 bg-transparent text-sm text-arang outline-none"
                            placeholder="••••••••"
                        >

                        <button
                            type="button"
                            @click="show = !show"
                            class="shrink-0 text-xs font-semibold text-daun hover:text-daun-tua"
                            x-text="show ? 'Sembunyi' : 'Lihat'"
                        ></button>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-sm text-kabut">
                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-garis text-daun focus:ring-daun"
                    >
                    <span>Ingat saya</span>
                </label>

                <button
                    type="submit"
                    class="w-full rounded-full bg-daun px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua"
                >
                    Masuk
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
