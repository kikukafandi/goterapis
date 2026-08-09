@extends('layouts.app')
@section('title', 'Saldo Terapis')
@section('content')
@php
    $statusLabel = ['requested' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $statusBadge = ['requested' => 'bg-kunyit-muda text-kunyit-tua', 'approved' => 'bg-daun-muda text-daun-tua', 'rejected' => 'bg-jahe-muda text-jahe'];
    $rekeningLengkap = $profile->bank_name && $profile->bank_account_number && $profile->bank_account_name;
@endphp

<div class="relative overflow-hidden bg-daun-terang px-5 pb-7 pt-4 text-white">
    <span class="pointer-events-none absolute -right-14 -top-20 h-48 w-48 rounded-full bg-white/10 lg:-right-20 lg:-top-44 lg:h-[26rem] lg:w-[26rem]"></span>
    <div class="mx-auto max-w-3xl">
        <h1 class="font-display text-[22px] font-extrabold">Saldo</h1>
        <p class="mt-6 text-xs font-medium text-white/80">Saldo tersedia</p>
        <p class="font-display mt-2 text-[40px] font-extrabold leading-none tracking-tight">Rp{{ number_format($available, 0, ',', '.') }}</p>
        <p class="mt-2 text-xs font-medium text-white/80">Rp{{ number_format($pending, 0, ',', '.') }} masih tertahan · Rp{{ number_format($withdrawn, 0, ',', '.') }} sudah ditarik</p>
        <a href="#tarik-dana" class="mt-5 flex w-full items-center justify-center rounded-2xl bg-white px-5 py-4 text-sm font-bold text-daun">Tarik dana</a>
    </div>
</div>

<div class="mx-auto flex max-w-3xl flex-col gap-4 px-5 pb-28 pt-4 lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
    <section class="space-y-3">
        <h2 class="font-display text-base font-extrabold text-arang">Pendapatan terbaru</h2>
        @forelse ($earnings as $earning)
            <div class="kartu flex items-center gap-3 px-4 py-[15px]">
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-bold text-arang">{{ $earning->order->user->name }}</span>
                    <span class="mt-1 block truncate text-[11px] text-kabut-samar">{{ $earning->order->code }} · {{ $earning->created_at->translatedFormat('j M Y') }}</span>
                    <span class="mt-1 block text-[10px] font-semibold {{ $earning->available_at->isFuture() ? 'text-kunyit-tua' : 'text-daun' }}">{{ $earning->available_at->isFuture() ? 'Tertahan' : 'Tersedia' }}</span>
                </span>
                <span class="font-display shrink-0 text-[15px] font-extrabold text-daun">+Rp{{ number_format($earning->amount, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="kartu px-5 py-8 text-center text-xs text-kabut-muda">Belum ada pendapatan.</div>
        @endforelse
    </section>

    <div class="space-y-4 lg:row-span-2">
        <section id="tarik-dana" class="kartu p-5">
            <h2 class="font-display text-base font-extrabold text-arang">Penarikan dana</h2>
            <p class="mt-2 text-xs text-kabut-muda">{{ $rekeningLengkap ? $profile->bank_name.' · '.$profile->bank_account_number.' · a.n. '.$profile->bank_account_name : 'Rekening tujuan belum lengkap.' }}</p>
            <form method="post" action="{{ route('mitra.withdrawals.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-arang">Nominal</span>
                    <input name="amount" type="number" min="10000" max="{{ max(0, $available) }}" required placeholder="Minimal Rp10.000" class="isian font-bold">
                </label>
                <button class="btn-utama w-full text-sm" @disabled(! $rekeningLengkap || $available < 10000)>Ajukan penarikan</button>
            </form>
            @error('amount')<p class="mt-2 text-xs font-medium text-jahe">{{ $message }}</p>@enderror
            <a href="{{ route('mitra.profil.edit') }}" class="mt-3 inline-block text-xs font-bold text-daun">Ubah rekening tujuan →</a>
        </section>

        <section class="space-y-3">
            <h2 class="font-display text-base font-extrabold text-arang">Riwayat penarikan</h2>
            @forelse ($withdrawals as $withdrawal)
                <div class="kartu flex items-center gap-3 px-4 py-[15px]">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-extrabold text-arang">Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}</span>
                        <span class="mt-1 block truncate text-[11px] text-kabut-samar">{{ $withdrawal->bank_name }} · {{ $withdrawal->created_at->translatedFormat('j M Y') }}</span>
                        @if ($withdrawal->rejection_reason)<span class="mt-1 block text-[11px] text-jahe">{{ $withdrawal->rejection_reason }}</span>@endif
                    </span>
                    <span class="shrink-0 rounded-full px-3 py-2 text-[10px] font-bold {{ $statusBadge[$withdrawal->status] ?? 'bg-kertas text-kabut' }}">{{ $statusLabel[$withdrawal->status] ?? $withdrawal->status }}</span>
                </div>
            @empty
                <div class="kartu px-5 py-8 text-center text-xs text-kabut-muda">Belum ada penarikan.</div>
            @endforelse
            @if ($withdrawals->hasPages())<div>{{ $withdrawals->links() }}</div>@endif
        </section>
    </div>
</div>
@endsection
