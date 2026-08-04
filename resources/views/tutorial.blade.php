@extends('layouts.app')
@section('title', 'Bantuan dan tutorial')

@section('content')
@php
    $steps = auth()->user()->isTherapist()
        ? ['Terima pesanan pelanggan', 'Tandai OTW untuk layanan panggilan', 'Mulai layanan dengan PIN pelanggan', 'Minta pelanggan mengonfirmasi selesai']
        : ['Pilih terapis dan buat pesanan', 'Bayar setelah terapis menerima', 'Pantau status dan posisi layanan', 'Konfirmasi selesai lalu beri ulasan'];
@endphp
<div class="mx-auto max-w-2xl px-4 pb-28 pt-6">
    <h1 class="font-display text-2xl font-bold text-arang">Bantuan dan tutorial</h1>
    <p class="mt-2 text-sm text-kabut">Ikuti langkah sederhana berikut untuk menggunakan GoTerapis.</p>
    <ol class="mt-6 space-y-3">
        @foreach ($steps as $step)
            <li class="flex gap-3 rounded-card border border-garis bg-white p-4">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-daun text-sm font-bold text-white">{{ $loop->iteration }}</span>
                <p class="pt-1 text-sm font-semibold text-arang">{{ $step }}</p>
            </li>
        @endforeach
    </ol>
</div>
@endsection
