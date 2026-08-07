@extends('layouts.admin')
@section('title', 'Penarikan')
@section('heading', 'Penarikan dana')
@section('subheading', 'Setujui atau tolak permintaan pencairan saldo mitra')
@section('content')
@php
    $statusLabel = ['requested' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $statusBadge = [
        'requested' => 'bg-kunyit-muda text-kunyit-tua',
        'approved' => 'bg-daun-muda text-daun-tua',
        'rejected' => 'bg-jahe-muda text-jahe',
    ];
    $menunggu = $withdrawals->where('status', 'requested');
@endphp

<div class="flex flex-col gap-4">

    {{-- Ringkasan --}}
    <div class="grid gap-3.5 sm:grid-cols-3">
        <div class="kartu flex flex-col gap-2.5 p-5">
            <span class="text-[11px] font-semibold text-kabut-muda">Menunggu persetujuan</span>
            <span class="font-display text-[26px] font-extrabold {{ $menunggu->count() ? 'text-jahe' : 'text-arang' }}">{{ $menunggu->count() }}</span>
            <span class="text-[11px] font-medium text-kabut-samar">Total Rp{{ number_format($menunggu->sum('amount'), 0, ',', '.') }} di halaman ini</span>
        </div>
        <div class="kartu flex flex-col gap-2.5 p-5">
            <span class="text-[11px] font-semibold text-kabut-muda">Disetujui</span>
            <span class="font-display text-[26px] font-extrabold text-arang">Rp{{ number_format($withdrawals->where('status', 'approved')->sum('amount'), 0, ',', '.') }}</span>
            <span class="text-[11px] font-medium text-kabut-samar">{{ $withdrawals->where('status', 'approved')->count() }} permintaan di halaman ini</span>
        </div>
        <div class="kartu flex flex-col gap-2.5 p-5">
            <span class="text-[11px] font-semibold text-kabut-muda">Ditolak</span>
            <span class="font-display text-[26px] font-extrabold text-arang">{{ $withdrawals->where('status', 'rejected')->count() }}</span>
            <span class="text-[11px] font-medium text-kabut-samar">Saldo sudah dilepas kembali</span>
        </div>
    </div>

    {{-- Filter status --}}
    <div class="flex flex-wrap gap-2">
        @foreach ([null => 'Semua', 'requested' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
            <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $value])) }}"
               class="rounded-[10px] border px-3.5 py-2.5 text-xs font-semibold {{ $status === $value ? 'border-arang bg-arang text-white' : 'border-garis bg-white text-kabut hover:border-daun-terang' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="kartu overflow-hidden">
        <div class="hidden grid-cols-[1.6fr_1.2fr_1.4fr_1fr_240px] gap-3.5 border-b border-garis-muda bg-kertas-isian px-6 py-3.5 xl:grid">
            @foreach (['Terapis', 'Nominal', 'Rekening tujuan', 'Status', 'Tindakan'] as $h)
                <span class="text-[10px] font-bold uppercase tracking-[.06em] text-kabut-samar">{{ $h }}</span>
            @endforeach
        </div>

        @forelse ($withdrawals as $withdrawal)
            <div class="grid gap-3.5 border-b border-garis-muda px-4 py-4 last:border-0 xl:grid-cols-[1.6fr_1.2fr_1.4fr_1fr_240px] xl:items-center xl:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-daun-muda text-xs font-bold text-daun">{{ mb_substr($withdrawal->therapistProfile->user->name, 0, 1) }}</span>
                    <span class="flex min-w-0 flex-col gap-1">
                        <span class="truncate text-[13px] font-bold text-arang">{{ $withdrawal->therapistProfile->user->name }}</span>
                        <span class="truncate text-[11px] font-medium text-kabut-samar">{{ $withdrawal->created_at->translatedFormat('j M Y · H:i') }}</span>
                    </span>
                </div>

                <span class="font-display text-[15px] font-extrabold text-arang">Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}</span>

                <span class="flex min-w-0 flex-col gap-1">
                    <span class="truncate text-xs font-semibold text-kabut">{{ $withdrawal->bank_name }} · {{ $withdrawal->bank_account_number }}</span>
                    <span class="truncate text-[11px] font-medium text-kabut-samar">a.n. {{ $withdrawal->bank_account_name }}</span>
                </span>

                <span class="justify-self-start rounded-full px-3 py-2 text-[10px] font-bold {{ $statusBadge[$withdrawal->status] ?? 'bg-kertas text-kabut' }}">{{ $statusLabel[$withdrawal->status] ?? $withdrawal->status }}</span>

                @if ($withdrawal->status === 'requested')
                    <div class="flex flex-col gap-2">
                        <form method="post" action="{{ route('admin.withdrawals.approve', $withdrawal) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <label class="min-w-0 flex-1">
                                <span class="sr-only">Referensi transfer</span>
                                <input name="transfer_reference" required maxlength="255" placeholder="Referensi transfer" class="isian px-3 py-2.5 text-xs">
                            </label>
                            <button class="shrink-0 rounded-xl bg-daun px-4 py-3 text-[11px] font-bold text-white hover:bg-daun-tua">Setujui</button>
                        </form>
                        <form method="post" action="{{ route('admin.withdrawals.reject', $withdrawal) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <label class="min-w-0 flex-1">
                                <span class="sr-only">Alasan penolakan</span>
                                <input name="rejection_reason" required maxlength="1000" placeholder="Alasan penolakan" class="isian px-3 py-2.5 text-xs">
                            </label>
                            <button class="shrink-0 rounded-xl border-[1.5px] border-jahe-garis bg-white px-4 py-3 text-[11px] font-bold text-jahe hover:bg-jahe-muda">Tolak</button>
                        </form>
                    </div>
                @elseif ($withdrawal->transfer_reference)
                    <span class="text-[11px] font-medium text-kabut-samar">Ref: {{ $withdrawal->transfer_reference }}</span>
                @elseif ($withdrawal->rejection_reason)
                    <span class="text-[11px] font-medium leading-relaxed text-jahe">{{ $withdrawal->rejection_reason }}</span>
                @else
                    <span class="text-[11px] font-medium text-kabut-samar">—</span>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center gap-3 px-10 py-16 text-center">
                <span class="grid h-[76px] w-[76px] place-items-center rounded-full bg-garis-muda text-kabut-samar"><x-icon name="wallet" class="h-7 w-7" /></span>
                <p class="font-display text-base font-extrabold text-arang">Belum ada permintaan penarikan</p>
                <p class="max-w-xs text-xs leading-relaxed text-kabut-muda">Permintaan pencairan saldo mitra akan muncul di sini.</p>
            </div>
        @endforelse

        @if ($withdrawals->hasPages())
            <div class="border-t border-garis-muda px-4 py-4 sm:px-6">{{ $withdrawals->links() }}</div>
        @else
            <p class="px-4 py-4 text-xs font-medium text-kabut-samar sm:px-6">Menampilkan {{ $withdrawals->count() }} dari {{ $withdrawals->total() }} permintaan</p>
        @endif
    </div>
</div>
@endsection
