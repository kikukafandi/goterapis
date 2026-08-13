@extends('layouts.app')
@section('title', 'Notifikasi')

@php $isTherapist = auth()->user()->isTherapist(); @endphp
@section('content')
@if ($isTherapist)
    <div class="bg-daun-terang px-5 pb-5 pt-4 text-white">
        <div class="mx-auto flex max-w-3xl items-center gap-3">
            <a href="{{ route('mitra.dashboard') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/20" aria-label="Kembali"><x-icon name="arrow-left" class="h-4 w-4" /></a>
            <h1 class="font-display flex-1 text-xl font-extrabold">Notifikasi</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="post" action="{{ route('notifications.read-all') }}">@csrf @method('patch')<button class="text-xs font-bold">Tandai dibaca</button></form>
            @endif
        </div>
    </div>
@endif
<div class="mx-auto max-w-3xl px-5 pb-28 {{ $isTherapist ? 'pt-0' : 'pt-8' }}">
    @unless ($isTherapist)
        <div class="mb-6 flex items-end justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-widest text-daun">Kabar terbaru</p><h1 class="mt-1 font-display text-3xl font-bold text-arang">Notifikasi</h1></div>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="post" action="{{ route('notifications.read-all') }}">@csrf @method('patch')<button class="rounded-full border border-daun/25 bg-daun-muda px-4 py-2 text-sm font-semibold text-daun-tua">Tandai semua dibaca</button></form>
            @endif
        </div>
    @endunless

    @if ($notifications->isEmpty())
        <div class="px-6 py-14 text-center {{ $isTherapist ? '' : 'rounded-card border border-garis bg-white' }}">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="bell" class="h-7 w-7" /></span>
            <h2 class="mt-4 font-display text-lg font-bold text-arang">Belum ada kabar</h2>
            <p class="mt-1 text-sm text-kabut">{{ $isTherapist ? 'Pembaruan pesanan dan akun mitramu akan muncul di sini.' : 'Pembaruan pesananmu akan muncul di sini.' }}</p>
        </div>
    @else
        <div class="{{ $isTherapist ? '' : 'overflow-hidden rounded-card border border-garis bg-white' }}">
            @foreach ($notifications as $notification)
                <article class="flex gap-3 border-b border-garis-muda px-0 py-[15px] {{ $notification->read_at ? '' : 'bg-daun-muda/50' }} {{ $isTherapist ? '' : 'px-5' }}">
                    <span class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-xl {{ $notification->read_at ? 'bg-kertas text-kabut' : 'bg-daun-muda text-daun' }}"><x-icon name="bell" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start gap-3">
                            <p class="flex-1 text-[13px] leading-relaxed {{ $notification->read_at ? 'font-medium' : 'font-bold' }} text-arang">{{ $notification->data['message'] ?? 'Ada pembaruan baru.' }}</p>
                            @unless ($notification->read_at)<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-daun" aria-label="Belum dibaca"></span>@endunless
                        </div>
                        <p class="mt-1 text-[11px] text-kabut-samar">{{ $notification->created_at->diffForHumans() }}</p>
                        <form method="post" action="{{ route('notifications.read', $notification) }}" class="mt-2">@csrf @method('patch')<button class="text-xs font-bold text-daun">Buka{{ $notification->read_at === null ? ' dan tandai dibaca' : '' }}</button></form>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
