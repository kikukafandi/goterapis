@extends('layouts.admin')
@section('title', 'Verifikasi Terapis')
@section('heading', 'Verifikasi Terapis')
@section('content')
<header class="mb-6 border-b border-garis pb-5"><h2 class="font-display text-2xl font-bold text-arang">Verifikasi terapis</h2><p class="mt-1.5 text-sm leading-6 text-kabut">Periksa identitas, dokumen, dan status mitra yang bergabung.</p></header>
<form method="get" class="mb-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
    <label class="flex min-h-11 items-center gap-2 rounded-xl border border-garis bg-white px-3 focus-within:border-daun focus-within:ring-2 focus-within:ring-daun/10"><span class="sr-only">Cari nama atau telepon</span><x-icon name="search" class="h-5 w-5 text-kabut" /><input name="q" value="{{ request('q') }}" placeholder="Cari nama atau telepon…" class="w-full bg-transparent text-sm outline-none"></label>
    <select name="status" aria-label="Filter status verifikasi" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-garis bg-white px-3 text-sm outline-none focus:border-daun focus:ring-2 focus:ring-daun/10"><option value="">Semua status</option>@foreach (\App\Models\TherapistProfile::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
    <button class="min-h-11 rounded-full bg-daun px-5 text-sm font-semibold text-white hover:bg-daun-tua">Cari</button>
</form>
@if (request('q') || request('status'))<div class="mb-3 flex items-center justify-between text-xs text-kabut"><span>Filter aktif</span><a href="{{ route('admin.therapists') }}" class="font-semibold text-daun hover:underline">Hapus filter</a></div>@endif
<div class="overflow-hidden rounded-card border border-garis bg-white">
    @forelse ($therapists as $therapist)
        <a href="{{ route('admin.therapist', $therapist) }}" class="group flex items-center gap-3 border-b border-garis px-4 py-4 last:border-0 hover:bg-kertas sm:px-5">
            @if ($therapist->user->avatarUrl())<img src="{{ $therapist->user->avatarUrl() }}" alt="" loading="lazy" class="h-11 w-11 shrink-0 rounded-full object-cover">@else<span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-daun-muda font-semibold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>@endif
            <div class="min-w-0 flex-1"><p class="truncate font-semibold text-arang">{{ $therapist->user->name }}</p><div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-kabut"><span>{{ $therapist->user->phone ?? 'Nomor belum diisi' }}</span><span>· {{ $therapist->city ?? 'Wilayah belum diisi' }}</span><span class="rounded-full bg-daun-muda px-2 py-1 font-semibold text-daun-tua">{{ $therapist->statusLabel() }}</span></div></div>
            @if ($therapist->pending_count)<span class="shrink-0 rounded-full bg-kunyit-muda px-2.5 py-1 text-xs font-semibold text-arang">{{ $therapist->pending_count }} menunggu</span>@endif<x-icon name="arrow-right" class="hidden h-4 w-4 text-kabut group-hover:text-daun sm:block" />
        </a>
    @empty
        <div class="px-6 py-14 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="{{ request('q') || request('status') ? 'search' : 'leaf' }}" class="h-5 w-5" /></span><h3 class="mt-4 font-display text-lg font-bold text-arang">{{ request('q') || request('status') ? 'Tidak ada hasil' : 'Belum ada terapis terdaftar' }}</h3><p class="mt-1 text-sm text-kabut">{{ request('q') || request('status') ? 'Coba ubah kata pencarian atau hapus filter.' : 'Pendaftaran terapis baru akan muncul di sini.' }}</p>@if (request('q') || request('status'))<a href="{{ route('admin.therapists') }}" class="mt-5 inline-flex min-h-11 items-center rounded-full border border-garis px-5 text-sm font-semibold text-daun">Hapus filter</a>@endif</div>
    @endforelse
</div>
<div class="mt-4">{{ $therapists->links() }}</div>
@endsection
