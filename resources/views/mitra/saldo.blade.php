@extends('layouts.app')
@section('title', 'Saldo Terapis')
@section('content')
@php
    $statusLabel = ['requested' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $statusBadge = [
        'requested' => 'bg-kunyit-muda text-kunyit-tua',
        'approved' => 'bg-daun-muda text-daun-tua',
        'rejected' => 'bg-jahe-muda text-jahe',
    ];
    $rekeningLengkap = $profile->bank_name && $profile->bank_account_number;
@endphp

<div class="mx-auto flex max-w-3xl flex-col gap-4 px-4 pb-28 pt-6">
    <h1 class="font-display text-[22px] font-extrabold text-arang">Saldo &amp; penarikan</h1>

    {{-- Saldo tersedia — angka dominan --}}
    <div class="flex flex-col gap-2 rounded-card bg-malam p-6">
        <span class="text-[11px] font-semibold text-white/50">Saldo tersedia</span>
        <span class="font-display text-[40px] font-extrabold leading-none text-daun-neon">Rp{{ number_format($available, 0, ',', '.') }}</span>
        <span class="text-[11px] font-medium text-white/45">Bisa dicairkan ke rekeningmu kapan saja.</span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="kartu flex flex-col gap-2 p-5">
            <span class="text-[11px] font-semibold text-kabut-muda">Saldo tertahan</span>
            <span class="font-display text-2xl font-extrabold text-arang">Rp{{ number_format($pending, 0, ',', '.') }}</span>
            <span class="text-[11px] font-medium text-kabut-samar">Menunggu sesi selesai</span>
        </div>
        <div class="kartu flex flex-col gap-2 p-5">
            <span class="text-[11px] font-semibold text-kabut-muda">Sudah ditarik</span>
            <span class="font-display text-2xl font-extrabold text-arang">Rp{{ number_format($withdrawn, 0, ',', '.') }}</span>
            <span class="text-[11px] font-medium text-kabut-samar">Total sepanjang waktu</span>
        </div>
    </div>

    {{-- Form penarikan --}}
    <section class="kartu flex flex-col gap-4 p-5 sm:p-6">
        <div class="flex flex-col gap-1.5">
            <h2 class="font-display text-[17px] font-extrabold text-arang">Tarik dana</h2>
            @if ($rekeningLengkap)
                <p class="text-xs font-medium text-kabut-muda">
                    Ke {{ $profile->bank_name }} · {{ $profile->bank_account_number }} · a.n. {{ $profile->bank_account_name }}
                </p>
            @else
                <p class="text-xs font-medium text-jahe">Rekening tujuan belum lengkap — lengkapi dulu di profil.</p>
            @endif
        </div>

        <form method="post" action="{{ route('mitra.withdrawals.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <label class="flex min-w-0 flex-1 flex-col gap-1.5">
                <span class="text-xs font-semibold text-arang">Jumlah penarikan</span>
                <input name="amount" type="number" min="10000" max="{{ max(0, $available) }}" required
                       placeholder="Minimal Rp10.000" class="isian">
            </label>
            <button class="btn-utama shrink-0 text-sm" @disabled(! $rekeningLengkap || $available < 10000)>Ajukan penarikan</button>
        </form>

        @error('amount')<p class="text-xs font-medium text-jahe">{{ $message }}</p>@enderror

        <a href="{{ route('mitra.profil.edit') }}" class="text-xs font-bold text-daun hover:text-daun-tua">Ubah rekening tujuan →</a>
    </section>

    {{-- Riwayat --}}
    <section class="kartu flex flex-col gap-4 p-5 sm:p-6">
        <h2 class="font-display text-[17px] font-extrabold text-arang">Riwayat penarikan</h2>

        @forelse ($withdrawals as $withdrawal)
            <div class="flex items-start justify-between gap-4 border-b border-garis-muda pb-4 last:border-0 last:pb-0">
                <span class="flex min-w-0 flex-col gap-1">
                    <span class="font-display text-base font-extrabold text-arang">Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}</span>
                    <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $withdrawal->bank_name }} · {{ $withdrawal->bank_account_number }}</span>
                    <span class="text-[11px] font-medium text-kabut-samar">{{ $withdrawal->created_at->translatedFormat('j M Y · H:i') }}</span>
                    @if ($withdrawal->rejection_reason)
                        <span class="text-[11px] font-medium leading-relaxed text-jahe">{{ $withdrawal->rejection_reason }}</span>
                    @endif
                </span>
                <span class="shrink-0 rounded-full px-3 py-2 text-[10px] font-bold {{ $statusBadge[$withdrawal->status] ?? 'bg-kertas text-kabut' }}">
                    {{ $statusLabel[$withdrawal->status] ?? $withdrawal->status }}
                </span>
            </div>
        @empty
            <p class="py-4 text-[13px] font-medium text-kabut-muda">Belum ada penarikan. Saldo yang tersedia bisa kamu cairkan lewat form di atas.</p>
        @endforelse

        @if ($withdrawals->hasPages())
            <div>{{ $withdrawals->links() }}</div>
        @endif
    </section>
</div>
@endsection
