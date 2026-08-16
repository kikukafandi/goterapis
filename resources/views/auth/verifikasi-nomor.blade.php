@extends('layouts.bare')
@section('title', 'Lengkapi data akun — GoTerapis')

@php($user = auth()->user())

@section('content')
<div class="flex min-h-dvh items-center justify-center bg-white px-5 py-10 sm:px-6">
    <div class="flex w-full max-w-sm flex-col">

        <a href="/" class="flex flex-col items-center gap-3.5">
            <x-logo variant="full" class="h-24" />
        </a>

        <h1 class="mt-9 text-center font-display text-2xl font-extrabold text-arang">
            {{ $user->gender === null ? 'Lengkapi data akunmu' : 'Verifikasi nomor WhatsApp' }}
        </h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-kabut-muda text-pretty">
            @if ($user->phone_verified_at === null)
                Kami kirim kode 6 digit lewat WhatsApp. Nomor ini dipakai terapis untuk menghubungimu saat pesanan berjalan.
            @else
                Tinggal satu langkah lagi sebelum kamu bisa memesan terapis.
            @endif
        </p>

        <x-flash class="mt-6" />

        {{-- Jenis kelamin: dasar pencocokan pelanggan–terapis, jadi wajib sebelum memesan. --}}
        @if ($user->gender === null)
            <form method="post" action="{{ route('gender.store') }}" class="mt-6 flex flex-col gap-3">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-semibold text-arang">Jenis kelamin</p>
                    <p class="text-xs leading-5 text-kabut-muda">
                        Terapis hanya melayani pelanggan dengan jenis kelamin yang sama. Pilihan ini tidak bisa diubah sendiri.
                    </p>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @foreach (['pria' => 'Pria', 'wanita' => 'Wanita'] as $value => $label)
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-garis bg-white px-3 py-2.5 text-sm font-semibold text-arang has-[:checked]:border-daun has-[:checked]:bg-daun-muda">
                                <input type="radio" name="gender" value="{{ $value }}" required @checked(old('gender') === $value) class="accent-daun">{{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('gender')<p class="text-xs font-medium text-jahe">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-utama w-full text-[15px]">Simpan jenis kelamin</button>
            </form>
        @endif

        @if ($user->phone_verified_at === null)
            {{-- Nomor bisa diperbaiki di sini kalau salah ketik saat daftar. --}}
            <form method="post" action="{{ route('phone.send') }}" class="mt-6 flex flex-col gap-3">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="text-xs font-semibold text-arang">Nomor WhatsApp</label>
                    <div class="flex gap-2">
                        <input id="phone" name="phone" type="tel" inputmode="tel" required
                               value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx" class="isian">
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
        @endif

        <p class="mt-10 text-center text-[13px] font-medium text-kabut-muda">
            Belum sempat? <a href="/" class="font-bold text-daun hover:text-daun-tua">Nanti saja</a> — data ini tetap harus lengkap sebelum memesan.
        </p>
    </div>
</div>
@endsection
