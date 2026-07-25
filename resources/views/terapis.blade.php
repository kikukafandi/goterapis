@extends('layouts.app')
@section('title', $therapist->user->name)

@php
    $u = $therapist->user;
    $statusLabel = $therapist->statusLabel();
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-6">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('cari') }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">← Kembali</a>

    {{-- Kepala profil --}}
    <div class="rounded-card border border-garis bg-white p-5 sm:p-6">
        <div class="flex gap-4">
            <div class="relative shrink-0">
                <img src="{{ $u->avatarUrl() ?? 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=E7EFE6&color=2E5A39' }}"
                     alt="{{ $u->name }}" class="h-24 w-24 rounded-2xl object-cover sm:h-28 sm:w-28">
                <x-badge :status="$therapist->verification_status" size="h-10 w-10"
                         class="absolute -bottom-2 -right-2 drop-shadow-sm" />
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="font-display text-2xl font-bold text-arang">{{ $u->name }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    <span class="inline-flex items-center gap-1">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-kunyit" fill="currentColor"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                        <span class="font-semibold text-arang">{{ number_format($therapist->rating_avg, 1, ',', '') }}</span>
                        <span class="text-kabut">({{ $therapist->reviews_count }} ulasan)</span>
                    </span>
                    <span class="text-garis">·</span>
                    <span class="text-kabut">{{ $therapist->experience_years }} th pengalaman</span>
                </p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center gap-1 rounded-full bg-daun-muda px-2.5 py-1 text-xs font-semibold text-daun-tua">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-daun" fill="currentColor"><path d="M20 4C10 4 4 10 4 20c8 0 16-6 16-16Z"/></svg>
                        {{ $statusLabel }}
                    </span>
                    @if ($therapist->serves_call)<span class="chip">Panggilan</span>@endif
                    @if ($therapist->serves_place)<span class="chip">Tempat praktik</span>@endif
                </div>
            </div>
        </div>

        @if ($therapist->bio)
            <p class="mt-5 border-t border-garis pt-4 text-sm leading-relaxed text-kabut">{{ $therapist->bio }}</p>
        @endif
    </div>

    {{-- Layanan & harga --}}
    <div class="mt-5 rounded-card border border-garis bg-white p-5 sm:p-6">
        <h2 class="font-semibold text-arang">Layanan & harga</h2>
        <ul class="mt-4 divide-y divide-garis">
            @foreach ($therapist->services as $s)
                <li class="flex items-center justify-between gap-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-arang">{{ $s->name }}</p>
                        <p class="text-xs text-kabut">{{ $s->pivot->duration_min }} menit</p>
                    </div>
                    <p class="shrink-0 text-sm font-bold text-arang">Rp{{ number_format($s->pivot->price, 0, ',', '.') }}</p>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Info layanan --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-card border border-garis bg-white p-5">
            <h2 class="flex items-center gap-2 font-semibold text-arang"><x-icon name="pin" class="h-5 w-5 text-daun" /> Wilayah</h2>
            <p class="mt-2 text-sm text-kabut">{{ $therapist->district ? $therapist->district.', ' : '' }}{{ $therapist->city }}{{ $therapist->province ? ', '.$therapist->province : '' }}</p>
            @if ($therapist->serves_call && $therapist->transport_fee)
                <p class="mt-2 text-sm text-kabut">Biaya transport: <span class="font-semibold text-arang">Rp{{ number_format($therapist->transport_fee, 0, ',', '.') }}</span></p>
            @endif
        </div>
        <div class="rounded-card border border-garis bg-white p-5">
            <h2 class="flex items-center gap-2 font-semibold text-arang"><x-icon name="calendar" class="h-5 w-5 text-daun" /> Jadwal</h2>
            @if ($therapist->schedules->isNotEmpty())
                <ul class="mt-2 space-y-1 text-sm text-kabut">
                    @foreach ($therapist->schedules as $sc)
                        <li>{{ $days[$sc->day_of_week] ?? '' }}: {{ substr($sc->start_time, 0, 5) }}–{{ substr($sc->end_time, 0, 5) }}</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-2 text-sm text-kabut">Jadwal fleksibel — pilih waktu saat memesan.</p>
            @endif
        </div>
    </div>

    {{-- Ulasan --}}
    <div class="mt-5 rounded-card border border-garis bg-white p-5 sm:p-6">
        <h2 class="font-semibold text-arang">Ulasan pelanggan</h2>
        @forelse ($therapist->reviews->where('is_hidden', false) as $r)
            <div class="mt-4 border-t border-garis pt-4 first:border-0 first:pt-2">
                <div class="flex items-center gap-1 text-kunyit">
                    @for ($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 {{ $i < round($r->averageRating()) ? '' : 'text-garis' }}" fill="currentColor"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/></svg>
                    @endfor
                </div>
                @if ($r->body)<p class="mt-2 text-sm text-arang">{{ $r->body }}</p>@endif
                <p class="mt-1 text-xs text-kabut">— {{ $r->user->name }}</p>
            </div>
        @empty
            <p class="mt-3 text-sm text-kabut">Belum ada ulasan. Jadilah yang pertama setelah memesan.</p>
        @endforelse
    </div>
</div>

{{-- Bar pesan (sticky) --}}
<div class="fixed inset-x-0 bottom-16 z-30 border-t border-garis bg-white/95 px-4 py-3 backdrop-blur md:bottom-0">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-4">
        <div>
            <p class="text-xs text-kabut">mulai</p>
            <p class="text-lg font-bold text-arang">Rp{{ number_format($therapist->services->min('pivot.price') ?? 0, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('pesan.create', $therapist) }}" class="flex-1 rounded-full bg-daun px-6 py-3 text-center text-sm font-semibold text-white transition-colors hover:bg-daun-tua sm:flex-none">
            Pesan sekarang
        </a>
    </div>
</div>
@endsection
