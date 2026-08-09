@extends('layouts.app')
@section('title', 'Beranda Mitra')

@section('content')
@php
    $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
@endphp
<div class="relative overflow-hidden bg-daun-terang px-4 pb-20 pt-6 text-white sm:px-6 lg:pb-24 lg:pt-10">
    <span class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10 lg:-right-20 lg:-top-48 lg:h-[30rem] lg:w-[30rem]"></span>
    <span class="pointer-events-none absolute -bottom-36 left-[42%] hidden h-72 w-72 rounded-full border-[44px] border-white/10 lg:block"></span>
    <div class="relative mx-auto max-w-6xl">
        <div class="flex min-w-0 items-center gap-3">
            @if ($profile->user->avatarUrl())
                <img src="{{ $profile->user->avatarUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-2xl object-cover sm:h-14 sm:w-14">
            @else
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white/15 text-lg font-extrabold sm:h-14 sm:w-14">{{ mb_substr($profile->user->name, 0, 1) }}</span>
            @endif
            <span class="min-w-0">
                <span class="block text-xs font-medium text-white/70">Selamat bekerja,</span>
                <h1 class="font-display truncate text-xl font-extrabold sm:text-2xl">{{ $profile->user->name }}</h1>
            </span>
        </div>

        <form method="post" action="{{ route('mitra.availability') }}" class="mt-6 flex items-center justify-between gap-4 rounded-card bg-white p-4 text-arang sm:max-w-md">
            @csrf
            @method('patch')
            <span>
                <strong class="block text-sm">{{ $profile->is_available ? 'Siap menerima pesanan' : 'Sedang tidak tersedia' }}</strong>
                <span class="mt-1 block text-[11px] text-kabut-muda">Atur status layananmu kapan saja</span>
            </span>
            <input type="hidden" name="is_available" value="{{ $profile->is_available ? 0 : 1 }}">
            <button aria-label="{{ $profile->is_available ? 'Nonaktifkan ketersediaan' : 'Aktifkan ketersediaan' }}" class="relative h-8 w-14 shrink-0 rounded-full {{ $profile->is_available ? 'bg-daun' : 'bg-garis' }}">
                <span class="absolute top-1 h-6 w-6 rounded-full bg-white transition-all {{ $profile->is_available ? 'left-7' : 'left-1' }}"></span>
            </button>
        </form>
    </div>
</div>

<div class="relative z-10 mx-auto -mt-12 grid max-w-6xl gap-5 px-4 pb-28 sm:px-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(300px,.8fr)] lg:gap-6 lg:pb-12">
    <div class="min-w-0 space-y-5">
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="kartu col-span-2 p-5 sm:col-span-1">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-kunyit-muda text-kunyit-tua"><x-icon name="wallet" /></span>
                <span class="mt-4 block text-[11px] font-semibold text-kabut-muda">Pendapatan hari ini</span>
                <strong class="font-display mt-1 block text-xl font-extrabold text-arang">Rp{{ number_format($todayEarnings, 0, ',', '.') }}</strong>
            </div>
            <div class="kartu p-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-daun-muda text-daun"><x-icon name="calendar" /></span>
                <span class="mt-4 block text-[11px] font-semibold text-kabut-muda">Sesi hari ini</span>
                <strong class="font-display mt-1 block text-2xl font-extrabold text-arang">{{ $todaySessions->count() }}</strong>
            </div>
            <div class="kartu p-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-jahe-muda text-jahe"><x-icon name="clipboard" /></span>
                <span class="mt-4 block text-[11px] font-semibold text-kabut-muda">Perlu konfirmasi</span>
                <strong class="font-display mt-1 block text-2xl font-extrabold text-arang">{{ $pendingOrders->count() }}</strong>
            </div>
        </section>

        <section class="kartu p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div><span class="text-[11px] font-semibold text-kabut-muda">Agenda kerja</span><h2 class="font-display mt-1 text-lg font-extrabold">Sesi hari ini</h2></div>
                <a href="{{ route('mitra.pesanan', ['tab' => 'berjalan']) }}" class="text-xs font-bold text-daun">Lihat semua</a>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($todaySessions as $order)
                    <a href="{{ route('mitra.pesanan.show', $order) }}" class="flex items-center gap-4 rounded-2xl bg-kertas-app p-4">
                        <span class="shrink-0 text-center"><strong class="font-display block text-lg text-arang">{{ $order->scheduled_at->format('H:i') }}</strong><span class="text-[10px] text-kabut-muda">{{ $order->duration_min }} mnt</span></span>
                        <span class="h-10 w-px bg-garis"></span>
                        <span class="min-w-0"><strong class="block truncate text-sm text-arang">{{ $order->user->name }}</strong><span class="mt-1 block truncate text-xs text-kabut-muda">{{ $order->service->name }} · {{ $order->model === 'panggilan' ? 'Panggilan' : 'Tempat praktik' }}</span></span>
                    </a>
                @empty
                    <p class="rounded-2xl bg-kertas-app px-4 py-8 text-center text-xs font-medium text-kabut-muda">Belum ada sesi untuk hari ini.</p>
                @endforelse
            </div>
        </section>

        <section class="kartu p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-display text-lg font-extrabold">Pesanan baru</h2><a href="{{ route('mitra.pesanan') }}" class="text-xs font-bold text-daun">Lihat semua</a></div>
            <div class="mt-4 divide-y divide-garis-muda">
                @forelse ($pendingOrders as $order)
                    <a href="{{ route('mitra.pesanan.show', $order) }}" class="flex items-center gap-3 py-4 first:pt-0 last:pb-0">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-daun-muda font-bold text-daun">{{ mb_substr($order->user->name, 0, 1) }}</span>
                        <span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ $order->user->name }}</strong><span class="mt-1 block truncate text-[11px] text-kabut-muda">{{ $order->service->name }} · {{ $order->scheduled_at->translatedFormat('d M, H:i') }}</span></span>
                        <strong class="text-xs text-arang">Rp{{ number_format($order->payout, 0, ',', '.') }}</strong>
                    </a>
                @empty
                    <p class="py-6 text-center text-xs font-medium text-kabut-muda">Tidak ada pesanan yang menunggu konfirmasi.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="kartu p-5 sm:p-6">
            <span class="text-[11px] font-semibold text-kabut-muda">Pendapatan sepanjang waktu</span>
            <strong class="font-display mt-2 block text-3xl font-extrabold">Rp{{ number_format($totalEarnings, 0, ',', '.') }}</strong>
            <a href="{{ route('mitra.saldo') }}" class="mt-5 flex items-center justify-between rounded-2xl bg-daun-muda px-4 py-3 text-xs font-bold text-daun-tua">Buka saldo <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </section>

        <section class="kartu p-5 sm:p-6">
            <div class="flex items-center justify-between"><h2 class="font-display text-lg font-extrabold">Jadwal mingguan</h2><a href="{{ route('mitra.profil.edit') }}" class="text-xs font-bold text-daun">Atur</a></div>
            <div class="mt-4 space-y-2">
                @forelse ($profile->schedules->sortBy('day_of_week') as $schedule)
                    <div class="flex items-center justify-between rounded-xl bg-kertas-app px-3 py-2.5 text-xs"><strong>{{ $dayNames[$schedule->day_of_week] }}</strong><span class="text-kabut-muda">{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</span></div>
                @empty
                    <p class="rounded-xl bg-kertas-app px-4 py-6 text-center text-xs text-kabut-muda">Jadwal belum diatur.</p>
                @endforelse
            </div>
        </section>
    </aside>
</div>
@endsection
