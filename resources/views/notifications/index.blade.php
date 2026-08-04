@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
<div class="mx-auto max-w-3xl px-4 pb-28 pt-8">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-daun">Kabar terbaru</p>
            <h1 class="mt-1 font-display text-3xl font-bold text-arang">Notifikasi</h1>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="post" action="{{ route('notifications.read-all') }}">
                @csrf @method('patch')
                <button class="rounded-full border border-daun/25 bg-daun-muda px-4 py-2 text-sm font-semibold text-daun-tua hover:bg-daun/15">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="rounded-card border border-garis bg-white px-6 py-14 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="bell" class="h-7 w-7" /></span>
            <h2 class="mt-4 font-display text-lg font-bold text-arang">Belum ada kabar</h2>
            <p class="mt-1 text-sm text-kabut">Pembaruan pesananmu akan muncul di sini.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-card border border-garis bg-white">
            @foreach ($notifications as $notification)
                <article class="flex gap-4 border-b border-garis p-5 last:border-0 {{ $notification->read_at ? '' : 'bg-daun-muda/50' }}">
                    <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-kertas text-kabut' : 'bg-daun text-white' }}">
                        <x-icon name="bell" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm {{ $notification->read_at ? 'text-arang' : 'font-semibold text-arang' }}">{{ $notification->data['message'] ?? 'Ada pembaruan baru.' }}</p>
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-garis' : 'bg-kunyit' }}" aria-label="{{ $notification->read_at ? 'Sudah dibaca' : 'Belum dibaca' }}"></span>
                        </div>
                        <p class="mt-1 text-xs text-kabut">{{ $notification->created_at->diffForHumans() }}</p>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold">
                            <a href="{{ $notification->data['url'] ?? route('notifications.index') }}" class="text-daun hover:underline">Buka</a>
                            @if ($notification->read_at === null)
                                <form method="post" action="{{ route('notifications.read', $notification) }}">
                                    @csrf @method('patch')
                                    <button class="text-kabut hover:text-daun">Buka dan tandai dibaca</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
