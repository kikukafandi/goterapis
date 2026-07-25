@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@php
    $cards = [
        ['label' => 'Pengguna', 'value' => $stats['users'], 'icon' => 'user'],
        ['label' => 'Terapis', 'value' => $stats['therapists'], 'icon' => 'leaf'],
        ['label' => 'Dokumen menunggu', 'value' => $stats['pending_docs'], 'icon' => 'shield', 'hot' => $stats['pending_docs'] > 0],
        ['label' => 'Pesanan', 'value' => $stats['orders'], 'icon' => 'clipboard'],
        ['label' => 'Pengaduan terbuka', 'value' => $stats['open_reports'], 'icon' => 'chat', 'hot' => $stats['open_reports'] > 0],
    ];
@endphp

<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
    @foreach ($cards as $c)
        <div class="rounded-2xl border border-garis bg-white p-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl {{ ($c['hot'] ?? false) ? 'bg-jahe/15 text-jahe' : 'bg-daun-muda text-daun' }}">
                <x-icon :name="$c['icon']" class="h-5 w-5" />
            </span>
            <p class="mt-3 text-2xl font-bold text-arang">{{ number_format($c['value'], 0, ',', '.') }}</p>
            <p class="text-xs text-kabut">{{ $c['label'] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 rounded-2xl border border-garis bg-white">
    <div class="flex items-center justify-between border-b border-garis px-5 py-4">
        <h2 class="font-semibold text-arang">Terapis terbaru</h2>
        <a href="{{ route('admin.therapists') }}" class="text-sm font-semibold text-daun hover:underline">Lihat semua</a>
    </div>
    @forelse ($latest as $t)
        <a href="{{ route('admin.therapist', $t) }}" class="flex items-center gap-3 border-b border-garis px-5 py-3 last:border-0 hover:bg-kertas">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-daun-muda font-semibold text-daun">
                {{ mb_substr($t->user->name, 0, 1) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-arang">{{ $t->user->name }}</p>
                <p class="truncate text-xs text-kabut">{{ $t->city ?? 'Wilayah belum diisi' }} · {{ \App\Models\TherapistProfile::STATUS_LABELS[$t->verification_status] }}</p>
            </div>
            @if ($t->pending_count)
                <span class="rounded-full bg-jahe/15 px-2.5 py-1 text-xs font-semibold text-jahe">{{ $t->pending_count }} dok. menunggu</span>
            @endif
            <x-icon name="arrow-right" class="h-4 w-4 text-kabut" />
        </a>
    @empty
        <p class="px-5 py-8 text-center text-sm text-kabut">Belum ada terapis terdaftar.</p>
    @endforelse
</div>
@endsection
