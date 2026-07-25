@extends('layouts.admin')
@section('title', 'Verifikasi Terapis')
@section('heading', 'Verifikasi Terapis')

@section('content')
<form method="get" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <div class="flex flex-1 items-center gap-2 rounded-xl border border-garis bg-white px-3 py-2 focus-within:border-daun">
        <x-icon name="search" class="h-5 w-5 text-kabut" />
        <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau telepon…"
               class="w-full bg-transparent text-sm outline-none">
    </div>
    <select name="status" onchange="this.form.submit()"
            class="rounded-xl border border-garis bg-white px-3 py-2 text-sm outline-none focus:border-daun">
        <option value="">Semua status</option>
        @foreach (\App\Models\TherapistProfile::STATUS_LABELS as $k => $v)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <button class="rounded-xl bg-daun px-4 py-2 text-sm font-semibold text-white hover:bg-daun-tua">Cari</button>
</form>

<div class="overflow-hidden rounded-2xl border border-garis bg-white">
    @forelse ($therapists as $t)
        <a href="{{ route('admin.therapist', $t) }}" class="flex items-center gap-3 border-b border-garis px-4 py-3 last:border-0 hover:bg-kertas">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-daun-muda font-semibold text-daun">
                {{ mb_substr($t->user->name, 0, 1) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-arang">{{ $t->user->name }}</p>
                <p class="truncate text-xs text-kabut">{{ $t->user->phone ?? '—' }} · {{ $t->city ?? 'Wilayah belum diisi' }}</p>
            </div>
            <span class="hidden rounded-full bg-daun-muda px-2.5 py-1 text-xs font-semibold text-daun-tua sm:inline">
                {{ \App\Models\TherapistProfile::STATUS_LABELS[$t->verification_status] }}
            </span>
            @if ($t->pending_count)
                <span class="rounded-full bg-jahe/15 px-2.5 py-1 text-xs font-semibold text-jahe">{{ $t->pending_count }}</span>
            @endif
            <x-icon name="arrow-right" class="h-4 w-4 text-kabut" />
        </a>
    @empty
        <p class="px-4 py-10 text-center text-sm text-kabut">Tidak ada terapis yang cocok.</p>
    @endforelse
</div>

<div class="mt-4">{{ $therapists->links() }}</div>
@endsection
