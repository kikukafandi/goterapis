@extends('layouts.app')
@section('title', 'Akun')

@php
    $user = auth()->user();
    $profile = $user->role === 'therapist' ? $user->therapistProfile : null;

    $menu = $user->role === 'therapist'
        ? array_filter([
            ['label' => 'Panel mitra', 'href' => route('mitra.dashboard')],
            ['label' => 'Pesanan saya', 'href' => route('pesanan.index')],
            ['label' => 'Edit profil mitra', 'href' => route('mitra.profil.edit')],
            ['label' => 'Pesanan masuk', 'href' => route('mitra.pesanan')],
            ['label' => 'Saldo & penarikan', 'href' => route('mitra.saldo')],
            $profile ? ['label' => 'Lihat profil publik saya', 'href' => route('terapis.show', $profile)] : null,
        ])
        : [
            ['label' => 'Pesanan saya', 'href' => route('pesanan.index')],
            ['label' => 'Percakapan', 'href' => route('chat')],
            ['label' => 'Toko', 'href' => route('products.index')],
            ['label' => 'Jurnal kesehatan', 'href' => route('artikel.index')],
        ];

    $menuBantuan = [
        ['label' => 'Bantuan dan tutorial', 'href' => route('tutorial')],
        ...collect(config('legal.documents'))->map(fn ($doc, $slug) => [
            'label' => $doc['title'], 'href' => route('legal.show', $slug),
        ])->values()->all(),
    ];
@endphp

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-4 px-4 pb-28 pt-6">
    <h1 class="font-display text-[22px] font-extrabold text-arang">Akun saya</h1>

    {{-- Kartu profil --}}
    <div class="kartu flex items-center gap-4 p-5">
        @if ($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-full object-cover">
        @else
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-daun-muda text-lg font-extrabold text-daun">{{ mb_substr($user->name, 0, 1) }}</span>
        @endif
        <span class="flex min-w-0 flex-1 flex-col gap-1">
            <span class="truncate text-base font-bold text-arang">{{ $user->name }}</span>
            <span class="truncate text-xs font-medium text-kabut-muda">{{ $user->email }}</span>
        </span>
    </div>

    <div class="kartu overflow-hidden">
        @foreach ($menu as $m)
            <a href="{{ $m['href'] }}" class="flex items-center justify-between gap-3 border-b border-garis-muda px-5 py-4 text-[13px] font-semibold text-arang last:border-0 hover:bg-kertas-isian">
                {{ $m['label'] }} <span class="shrink-0 text-kabut-samar">›</span>
            </a>
        @endforeach
    </div>

    {{-- Ajakan jadi terapis --}}
    @if ($user->role === 'user')
        <a href="/daftar-terapis" class="flex flex-col gap-2 rounded-card bg-malam p-5">
            <span class="font-display text-[17px] font-extrabold text-white">Gabung jadi terapis</span>
            <span class="text-xs font-medium leading-relaxed text-white/55 text-pretty">Punya keahlian pijat, bekam, atau terapi tubuh? Terima pesanan di sekitarmu dan atur jadwalmu sendiri.</span>
            <span class="mt-1 text-xs font-bold text-daun-neon">Daftar sekarang →</span>
        </a>
    @endif

    <div class="kartu overflow-hidden">
        @foreach ($menuBantuan as $m)
            <a href="{{ $m['href'] }}" class="flex items-center justify-between gap-3 border-b border-garis-muda px-5 py-4 text-[13px] font-semibold text-arang last:border-0 hover:bg-kertas-isian">
                {{ $m['label'] }} <span class="shrink-0 text-kabut-samar">›</span>
            </a>
        @endforeach
    </div>

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="btn-garis w-full text-sm">Keluar</button>
    </form>
</div>
@endsection
