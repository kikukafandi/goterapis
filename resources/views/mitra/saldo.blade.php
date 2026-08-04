@extends('layouts.app')
@section('title', 'Saldo Terapis')
@section('content')
<div class="mx-auto max-w-4xl px-4 pb-28 pt-6">
    <h1 class="font-display text-3xl font-bold text-arang">Saldo & penarikan</h1>
    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        @foreach ([['Menunggu', $pending], ['Tersedia', $available], ['Sudah ditarik', $withdrawn]] as [$label, $amount])
            <div class="rounded-card border border-garis bg-white p-5"><p class="text-sm text-kabut">{{ $label }}</p><p class="mt-2 font-display text-2xl font-bold text-arang">Rp{{ number_format($amount, 0, ',', '.') }}</p></div>
        @endforeach
    </div>
    <section class="mt-6 rounded-card border border-garis bg-white p-5 sm:p-6">
        <h2 class="font-display text-xl font-bold text-arang">Tarik saldo</h2>
        <p class="mt-1 text-sm text-kabut">Ke {{ $profile->bank_name ?: 'rekening yang belum dilengkapi' }} · {{ $profile->bank_account_number ?: '—' }} · {{ $profile->bank_account_name ?: '—' }}</p>
        <form method="post" action="{{ route('mitra.withdrawals.store') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <x-field name="amount" label="Jumlah penarikan" type="number" min="10000" max="{{ max(0, $available) }}" required class="flex-1" />
            <button class="rounded-full bg-daun px-6 py-3 font-bold text-white hover:bg-daun-tua">Ajukan penarikan</button>
        </form>
        @error('amount') <p class="mt-2 text-sm text-jahe">{{ $message }}</p> @enderror
    </section>
    <section class="mt-6 rounded-card border border-garis bg-white p-5 sm:p-6">
        <h2 class="font-display text-xl font-bold text-arang">Riwayat penarikan</h2>
        <div class="mt-4 divide-y divide-garis">
            @forelse ($withdrawals as $withdrawal)
                <div class="flex items-start justify-between gap-4 py-4"><div><p class="font-semibold text-arang">Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}</p><p class="text-sm text-kabut">{{ $withdrawal->bank_name }} · {{ $withdrawal->bank_account_number }}</p>@if($withdrawal->rejection_reason)<p class="text-sm text-jahe">{{ $withdrawal->rejection_reason }}</p>@endif</div><x-badge>{{ ['requested' => 'Diminta', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$withdrawal->status] }}</x-badge></div>
            @empty <p class="py-5 text-sm text-kabut">Belum ada penarikan.</p> @endforelse
        </div>
        {{ $withdrawals->links() }}
    </section>
</div>
@endsection
