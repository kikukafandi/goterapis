@php
    $therapistShell = auth()->user()?->isTherapist() && request()->routeIs('mitra.*', 'chat', 'notifications.*');
    $navigationCities = $therapistShell ? [] : cache()->remember('navigation-cities', 3600, fn () => \App\Models\TherapistProfile::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city')->filter(fn ($city) => is_string($city) && $city !== '')->values()->all());
    $selectedCity = is_string(request('kota')) ? request('kota') : '';
    $therapistNavigation = [
        ['label' => 'Beranda', 'route' => 'mitra.dashboard'],
        ['label' => 'Pesanan', 'route' => 'mitra.pesanan'],
        ['label' => 'Pesan', 'route' => 'chat'],
        ['label' => 'Saldo', 'route' => 'mitra.saldo'],
        ['label' => 'Profil', 'route' => 'mitra.profil.edit'],
    ];
@endphp
<header x-data="{ mobile: false }" @keydown.escape.window="mobile = false" class="sticky top-0 z-50 border-b border-garis bg-white/95 backdrop-blur-xl">
    <div class="mx-auto flex h-[72px] max-w-6xl items-center gap-7 px-4">
        <a href="{{ $therapistShell ? route('mitra.dashboard') : route('home') }}" class="shrink-0" aria-label="GoTerapis"><x-logo variant="full" class="h-10" /></a>

        @if ($therapistShell)
            <span class="hidden rounded-full bg-daun-muda px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-daun-tua sm:block">Mitra</span>
            <nav class="ml-auto hidden items-center gap-1 lg:flex">
                @foreach ($therapistNavigation as $item)
                    <a href="{{ route($item['route']) }}" @class(['rounded-xl px-4 py-2.5 text-[13px] font-semibold', 'bg-daun-muda text-daun-tua' => request()->routeIs($item['route'], $item['route'].'.*'), 'text-arang hover:bg-kertas-app' => ! request()->routeIs($item['route'], $item['route'].'.*')])>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        @else
            @unless (request()->routeIs('cari'))
                <form action="{{ route('cari') }}" method="get" class="hidden min-w-[200px] max-w-[340px] flex-1 items-center rounded-[14px] border border-garis bg-kertas-app py-1 pl-3.5 pr-1 lg:flex">
                    <x-icon name="search" class="h-[17px] w-[17px] shrink-0 text-kabut-samar" />
                    <input name="q" value="{{ request('q') }}" placeholder="Layanan atau nama terapis" class="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-[13px] font-medium text-arang outline-none placeholder:text-kabut-samar">
                    <label class="flex shrink-0 items-center gap-1.5 rounded-[10px] bg-white px-3 py-2.5">
                        <select name="kota" onchange="this.form.submit()" aria-label="Pilih kota" class="max-w-28 appearance-none bg-transparent text-xs font-semibold text-arang outline-none"><option value="">Semua kota</option>@foreach ($navigationCities as $city)<option value="{{ $city }}" @selected($selectedCity === $city)>{{ $city }}</option>@endforeach</select>
                    </label>
                </form>
            @endunless
            <nav class="hidden shrink-0 items-center gap-5 lg:flex">
                <a href="{{ route('cari') }}" class="text-[13px] font-semibold {{ request()->routeIs('cari', 'terapis.show') ? 'text-daun' : 'text-arang' }}">Cari terapis</a>
                <a href="{{ route('artikel.index') }}" class="text-[13px] font-semibold {{ request()->routeIs('artikel.*') ? 'text-daun' : 'text-arang' }}">Jurnal</a>
                <a href="{{ route('products.index') }}" class="text-[13px] font-semibold {{ request()->routeIs('products.*') ? 'text-daun' : 'text-arang' }}">Toko</a>
                <a href="{{ route('register.therapist') }}" class="text-[13px] font-semibold {{ request()->routeIs('register.therapist') ? 'text-daun' : 'text-arang' }}">Jadi terapis</a>
            </nav>
        @endif

        <div class="ml-auto hidden shrink-0 items-center gap-3 lg:flex">
            @auth
                @unless ($therapistShell)
                    @if (auth()->user()->role === 'admin')<a href="{{ route('admin.dashboard') }}" class="text-[13px] font-semibold text-arang">Panel admin</a>@elseif (auth()->user()->role === 'therapist')<a href="{{ route('mitra.dashboard') }}" class="text-[13px] font-semibold text-arang">Panel mitra</a>@else<a href="{{ route('pesanan.index') }}" class="text-[13px] font-semibold text-arang">Pesanan saya</a>@endif
                    @if (auth()->user()->role !== 'admin')<a href="{{ route('chat') }}" class="text-[13px] font-semibold text-arang">Pesan</a>@endif
                @endunless
                <a href="{{ route('notifications.index') }}" aria-label="Notifikasi" class="relative grid h-[38px] w-[38px] place-items-center rounded-full bg-kertas-app text-arang"><x-icon name="bell" class="h-[18px] w-[18px]" />@if ($unread = auth()->user()->unreadNotifications()->count())<span class="absolute right-0 top-0 min-w-4 rounded-full bg-jahe-terang px-1 text-center text-[9px] font-bold leading-4 text-white">{{ $unread > 9 ? '9+' : $unread }}</span>@endif</a>
                <a href="{{ $therapistShell ? route('mitra.profil.edit') : route('akun') }}" class="flex items-center gap-2 rounded-full border border-garis px-1.5 py-1.5 pr-3 text-xs font-semibold text-arang">@if (auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-[30px] w-[30px] rounded-full object-cover">@else<span class="grid h-[30px] w-[30px] place-items-center rounded-full bg-daun-muda font-bold text-daun">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>@endif<span class="max-w-24 truncate">{{ auth()->user()->name }}</span></a>
                @if ($therapistShell)<form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-xl px-3 py-2.5 text-xs font-semibold text-jahe hover:bg-jahe-muda">Keluar</button></form>@endif
            @else
                <a href="{{ route('login') }}" class="text-[13px] font-semibold text-arang">Masuk</a><a href="{{ route('register') }}" class="rounded-xl bg-daun px-4 py-3 text-[13px] font-bold text-white">Daftar</a>
            @endauth
        </div>

        <div class="ml-auto flex items-center gap-1 lg:hidden">
            @unless ($therapistShell)<a href="{{ route('cari') }}" aria-label="Cari" class="grid h-10 w-10 place-items-center rounded-full text-arang"><x-icon name="search" class="h-5 w-5" /></a>@endunless
            @auth<a href="{{ route('notifications.index') }}" aria-label="Notifikasi" class="grid h-10 w-10 place-items-center rounded-full text-arang"><x-icon name="bell" class="h-5 w-5" /></a>@endauth
            <button type="button" @click="mobile = true" aria-label="Menu" class="grid h-10 w-10 place-items-center rounded-full text-arang"><x-icon name="menu" class="h-6 w-6" /></button>
        </div>
    </div>

    <div x-cloak x-show="mobile" class="fixed inset-0 z-50 lg:hidden" x-transition:enter="transition-opacity duration-300 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <button type="button" @click="mobile = false" aria-label="Tutup menu" class="absolute inset-0 bg-arang/40"></button>
        <div class="absolute right-0 top-0 flex h-dvh w-[82%] max-w-sm flex-col bg-white p-5 shadow-2xl transition-transform duration-300 ease-out" :class="mobile ? 'translate-x-0' : 'translate-x-full'" x-trap.noscroll="mobile">
            <div class="flex items-center justify-between"><x-logo variant="full" class="h-10" /><button type="button" @click="mobile = false" aria-label="Tutup"><x-icon name="close" class="h-6 w-6" /></button></div>
            @if ($therapistShell)
                <nav class="mt-6 border-t border-garis pt-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-kabut-muda">Menu lainnya</span>
                    <a href="{{ route('mitra.verifikasi') }}" class="mt-2 flex items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold text-arang hover:bg-kertas-app">Status verifikasi <x-icon name="arrow-right" class="h-4 w-4 text-kabut-muda" /></a>
                    <a href="{{ route('tutorial') }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold text-arang hover:bg-kertas-app">Bantuan & tutorial <x-icon name="arrow-right" class="h-4 w-4 text-kabut-muda" /></a>
                    <a href="{{ route('home') }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold text-arang hover:bg-kertas-app">Mode pelanggan <x-icon name="arrow-right" class="h-4 w-4 text-kabut-muda" /></a>
                </nav>
            @else
                <form action="{{ route('cari') }}" class="mt-6 flex items-center gap-2 rounded-xl bg-kertas-app px-4 py-3"><x-icon name="search" class="h-5 w-5 text-kabut" /><input name="q" placeholder="Cari terapis" class="min-w-0 flex-1 bg-transparent text-sm outline-none"></form>
                <nav class="mt-5 flex flex-col border-t border-garis pt-3"><a href="{{ route('cari') }}" class="py-3 text-sm font-semibold text-arang">Cari terapis</a><a href="{{ route('artikel.index') }}" class="py-3 text-sm font-semibold text-arang">Jurnal</a><a href="{{ route('products.index') }}" class="py-3 text-sm font-semibold text-arang">Toko</a><a href="{{ route('home') }}#cara-kerja" class="py-3 text-sm font-semibold text-arang">Cara kerja</a><a href="{{ route('register.therapist') }}" class="py-3 text-sm font-semibold text-arang">Jadi terapis</a></nav>
            @endif
            <div class="mt-auto border-t border-garis pt-5">@auth @unless ($therapistShell)<a href="{{ route('akun') }}" class="block rounded-xl border border-garis px-4 py-3 text-center text-sm font-bold text-arang">Profil</a>@endunless<form method="post" action="{{ route('logout') }}" class="{{ $therapistShell ? '' : 'mt-2' }}">@csrf<button class="w-full rounded-xl bg-daun px-4 py-3 text-sm font-bold text-white">Keluar</button></form>@else<a href="{{ route('register') }}" class="block rounded-xl bg-daun px-4 py-3 text-center text-sm font-bold text-white">Daftar</a><a href="{{ route('login') }}" class="mt-2 block rounded-xl border border-garis px-4 py-3 text-center text-sm font-bold text-arang">Masuk</a>@endauth</div>
        </div>
    </div>
</header>
