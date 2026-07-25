@extends('layouts.app')
@section('title', 'Akun')

@php
    $user = auth()->user();
    $profile = $user->role === 'therapist' ? $user->therapistProfile : null;
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 pb-28 pt-6">
    <h1 class="font-display text-2xl font-bold text-arang">Akun</h1>

    <div class="mt-5 rounded-card border border-garis bg-white p-5 sm:p-6">
        <div class="flex items-center gap-4">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover">
            @else
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-daun/10 text-lg font-bold text-daun">{{ mb_substr($user->name, 0, 1) }}</span>
            @endif
            <div class="min-w-0">
                <p class="truncate font-semibold text-arang">{{ $user->name }}</p>
                <p class="truncate text-sm text-kabut">{{ $user->email }}</p>
            </div>
        </div>
    </div>

    {{-- Pintasan sesuai peran --}}
    <div class="mt-5 space-y-2">
        @if ($user->role === 'therapist')
            <a href="{{ route('mitra.pesanan') }}" class="flex items-center justify-between rounded-card border border-garis bg-white px-5 py-4 text-sm font-semibold text-arang transition-colors hover:border-daun">
                Pesanan masuk <span class="text-kabut">›</span>
            </a>
            @if ($profile)
                <a href="{{ route('terapis.show', $profile) }}" class="flex items-center justify-between rounded-card border border-garis bg-white px-5 py-4 text-sm font-semibold text-arang transition-colors hover:border-daun">
                    Lihat profil publik <span class="text-kabut">›</span>
                </a>
            @endif
        @else
            <a href="{{ route('pesanan.index') }}" class="flex items-center justify-between rounded-card border border-garis bg-white px-5 py-4 text-sm font-semibold text-arang transition-colors hover:border-daun">
                Riwayat pesanan <span class="text-kabut">›</span>
            </a>
        @endif
    </div>

    <form method="post" action="{{ route('logout') }}" class="mt-5">
        @csrf
        <button class="w-full rounded-full border border-garis px-6 py-3 text-sm font-semibold text-arang transition-colors hover:bg-kertas">Keluar</button>
    </form>
</div>
@endsection
