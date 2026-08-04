@extends('layouts.admin')
@section('title', 'Penarikan')
@section('heading', 'Penarikan Terapis')
@section('content')
<div class="flex flex-wrap gap-2">
    @foreach ([null => 'Semua', 'requested' => 'Diminta', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
        <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $value])) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $value ? 'bg-daun text-white' : 'border border-garis bg-white text-arang' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="mt-5 space-y-4">
    @forelse ($withdrawals as $withdrawal)
        <article class="rounded-card border border-garis bg-white p-5">
            <div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-display text-lg font-bold text-arang">{{ $withdrawal->therapistProfile->user->name }}</h2><p class="text-sm text-kabut">{{ $withdrawal->bank_name }} · {{ $withdrawal->bank_account_number }} · {{ $withdrawal->bank_account_name }}</p></div><div class="text-right"><p class="font-display text-xl font-bold text-arang">Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}</p><p class="text-sm text-kabut">{{ $withdrawal->status }}</p></div></div>
            @if ($withdrawal->status === 'requested')
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <form method="post" action="{{ route('admin.withdrawals.approve', $withdrawal) }}" class="flex gap-2">@csrf @method('PATCH')<input name="transfer_reference" required maxlength="255" placeholder="Referensi transfer" class="min-w-0 flex-1 rounded-xl border border-garis px-3 py-2 text-sm"><button class="rounded-full bg-daun px-4 py-2 text-sm font-bold text-white">Setujui</button></form>
                    <form method="post" action="{{ route('admin.withdrawals.reject', $withdrawal) }}" class="flex gap-2">@csrf @method('PATCH')<input name="rejection_reason" required maxlength="1000" placeholder="Alasan penolakan" class="min-w-0 flex-1 rounded-xl border border-garis px-3 py-2 text-sm"><button class="rounded-full border border-jahe px-4 py-2 text-sm font-bold text-jahe">Tolak</button></form>
                </div>
            @elseif ($withdrawal->transfer_reference)<p class="mt-3 text-sm text-kabut">Referensi: {{ $withdrawal->transfer_reference }}</p>
            @elseif ($withdrawal->rejection_reason)<p class="mt-3 text-sm text-jahe">{{ $withdrawal->rejection_reason }}</p>@endif
        </article>
    @empty <div class="rounded-card border border-garis bg-white p-8 text-center text-kabut">Belum ada permintaan penarikan.</div> @endforelse
</div>
<div class="mt-5">{{ $withdrawals->links() }}</div>
@endsection
