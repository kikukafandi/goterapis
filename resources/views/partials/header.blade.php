@php
    $cats = [
        ['slug' => 'pijat', 'label' => 'Pijat', 'desc' => 'Tradisional, relaksasi, kebugaran'],
        ['slug' => 'bekam', 'label' => 'Bekam', 'desc' => 'Kering, basah, kebugaran'],
        ['slug' => 'kretek', 'label' => 'Kretek', 'desc' => 'Kretek & peregangan tubuh'],
        ['slug' => 'refleksi', 'label' => 'Refleksi', 'desc' => 'Refleksi kaki & tangan'],
        ['slug' => 'lainnya', 'label' => 'Kerik & Totok', 'desc' => 'Perawatan tradisional lain'],
    ];
@endphp

<header
    x-data="{ scrolled: false, mobile: false, cari: false, showSearch: false }"
    @scroll.window="scrolled = window.scrollY > 8; showSearch = window.scrollY > 520"
    @keydown.escape.window="mobile = false; cari = false"
    :class="scrolled && 'shadow-[0_1px_0_rgba(30,36,31,.06)]'"
    class="sticky top-0 z-50 border-b border-garis bg-white"
>
    <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 lg:gap-6">
        {{-- Logo --}}
        <a href="/" class="flex shrink-0 items-center gap-2 font-display text-xl font-semibold text-daun">
            <x-icon name="leaf" class="h-6 w-6" /> GoTerapis
        </a>

        {{-- Nav desktop (gaya Upwork: menu + mega-menu) --}}
        <nav class="hidden items-center gap-1 lg:flex">
            <div class="relative" @mouseenter="cari = true" @mouseleave="cari = false">
                <button type="button" @click="cari = !cari"
                        class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-arang hover:bg-kertas"
                        :class="cari && 'bg-kertas'">
                    Cari Terapis
                    <x-icon name="chevron-down" class="h-4 w-4 transition-transform" ::class="cari && 'rotate-180'" />
                </button>

                {{-- Mega-menu kategori --}}
                <div x-show="cari" x-cloak x-transition
                     class="absolute left-0 top-full w-[540px] pt-2">
                    <div class="rounded-2xl border border-garis bg-white p-3 shadow-lg">
                        <div class="grid grid-cols-2 gap-1">
                            @foreach ($cats as $c)
                                <a href="/cari?kategori={{ $c['slug'] }}"
                                   class="flex items-start gap-3 rounded-xl p-3 hover:bg-daun-muda">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-daun-muda text-daun">
                                        <x-cat-icon :slug="$c['slug']" class="h-5 w-5" />
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-arang">{{ $c['label'] }}</span>
                                        <span class="block text-xs text-kabut">{{ $c['desc'] }}</span>
                                    </span>
                                </a>
                            @endforeach
                            <a href="/cari" class="flex items-center justify-center gap-1.5 rounded-xl bg-daun p-3 text-sm font-semibold text-white hover:bg-daun-tua">
                                Semua terapis <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('artikel.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-arang hover:bg-kertas">Info Sehat</a>
            <a href="/#cara-kerja" class="rounded-lg px-3 py-2 text-sm font-semibold text-arang hover:bg-kertas">Cara Kerja</a>
            <a href="/daftar-terapis" class="rounded-lg px-3 py-2 text-sm font-semibold text-arang hover:bg-kertas">Jadi Terapis</a>
        </nav>

        {{-- Search: muncul di navbar setelah hero tergulir --}}
        <form action="/cari" method="get" x-show="showSearch" x-cloak x-transition
              class="ml-auto hidden max-w-xs flex-1 items-center rounded-full border border-garis bg-kertas px-3 py-2 focus-within:border-daun lg:flex">
            <x-icon name="search" class="h-4 w-4 shrink-0 text-kabut" />
            <input name="q" type="text" placeholder="Cari layanan atau terapis…"
                   class="w-full bg-transparent px-2 text-sm outline-none placeholder:text-kabut">
        </form>

        {{-- Auth desktop --}}
        <div class="hidden shrink-0 items-center gap-1.5 lg:ml-auto lg:flex">
            @auth
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-arang hover:bg-kertas">Panel Admin</a>
                @elseif (auth()->user()->role === 'therapist')
                    <a href="{{ route('mitra.pesanan') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->is('mitra*') ? 'text-daun' : 'text-arang' }} hover:bg-kertas">Pesanan masuk</a>
                @else
                    <a href="{{ route('pesanan.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->is('pesanan*') ? 'text-daun' : 'text-arang' }} hover:bg-kertas">Pesanan</a>
                @endif
                <a href="{{ route('akun') }}" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-semibold {{ request()->is('akun') ? 'text-daun' : 'text-arang' }} hover:bg-kertas">
                    <x-icon name="user" class="h-4 w-4" /> {{ auth()->user()->name }}
                </a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full bg-daun px-4 py-2 text-sm font-semibold text-white hover:bg-daun-tua">Keluar</button>
                </form>
            @else
                <a href="/masuk" class="rounded-full px-4 py-2 text-sm font-semibold text-arang hover:bg-kertas">Masuk</a>
                <a href="/daftar" class="rounded-full bg-daun px-4 py-2 text-sm font-semibold text-white hover:bg-daun-tua">Daftar</a>
            @endauth
        </div>

        {{-- Aksi mobile --}}
        <div class="ml-auto flex items-center gap-1 lg:hidden">
            <a href="/cari" aria-label="Cari" class="grid h-10 w-10 place-items-center rounded-full text-arang hover:bg-kertas">
                <x-icon name="search" class="h-5 w-5" />
            </a>
            <button type="button" @click="mobile = true" aria-label="Menu"
                    class="grid h-10 w-10 place-items-center rounded-full text-arang hover:bg-kertas">
                <x-icon name="menu" class="h-6 w-6" />
            </button>
        </div>
    </div>

    {{-- Drawer mobile --}}
    <div x-show="mobile" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-arang/40" @click="mobile = false" x-transition.opacity></div>
        <div class="absolute right-0 top-0 flex h-full w-[82%] max-w-sm flex-col bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             x-trap.noscroll="mobile">
            <div class="flex items-center justify-between border-b border-garis px-4 py-3">
                <span class="font-display text-lg font-semibold text-daun">Menu</span>
                <button type="button" @click="mobile = false" aria-label="Tutup" class="grid h-9 w-9 place-items-center rounded-full hover:bg-kertas">
                    <x-icon name="close" class="h-5 w-5" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <p class="px-1 text-xs font-bold uppercase tracking-wide text-kabut">Kategori</p>
                <div class="mt-2 space-y-1">
                    @foreach ($cats as $c)
                        <a href="/cari?kategori={{ $c['slug'] }}" class="flex items-center gap-3 rounded-xl p-2.5 hover:bg-daun-muda">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-daun-muda text-daun">
                                <x-cat-icon :slug="$c['slug']" class="h-5 w-5" />
                            </span>
                            <span class="text-sm font-semibold text-arang">{{ $c['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4 space-y-1 border-t border-garis pt-4">
                    <a href="/cari" class="block rounded-xl p-2.5 text-sm font-semibold text-arang hover:bg-kertas">Semua terapis</a>
                    <a href="{{ route('artikel.index') }}" class="block rounded-xl p-2.5 text-sm font-semibold text-arang hover:bg-kertas">Info Sehat</a>
                    <a href="/#cara-kerja" class="block rounded-xl p-2.5 text-sm font-semibold text-arang hover:bg-kertas">Cara Kerja</a>
                    <a href="/daftar-terapis" class="block rounded-xl p-2.5 text-sm font-semibold text-arang hover:bg-kertas">Jadi Terapis</a>
                </div>
            </div>
            <div class="border-t border-garis p-4">
                @auth
                    <p class="px-1 pb-2 text-sm text-kabut">Masuk sebagai <span class="font-semibold text-arang">{{ auth()->user()->name }}</span></p>
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="mb-2 block rounded-full border border-garis px-4 py-3 text-center text-sm font-semibold text-arang hover:bg-kertas">Panel Admin</a>
                    @elseif (auth()->user()->role === 'therapist')
                        <a href="{{ route('mitra.pesanan') }}" class="mb-2 block rounded-full border border-garis px-4 py-3 text-center text-sm font-semibold text-arang hover:bg-kertas">Pesanan masuk</a>
                    @else
                        <a href="{{ route('pesanan.index') }}" class="mb-2 block rounded-full border border-garis px-4 py-3 text-center text-sm font-semibold text-arang hover:bg-kertas">Pesanan saya</a>
                    @endif
                    <a href="{{ route('akun') }}" class="mb-2 block rounded-full border border-garis px-4 py-3 text-center text-sm font-semibold text-arang hover:bg-kertas">Akun</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="block w-full rounded-full bg-daun px-4 py-3 text-center text-sm font-semibold text-white hover:bg-daun-tua">Keluar</button>
                    </form>
                @else
                    <a href="/daftar" class="block rounded-full bg-daun px-4 py-3 text-center text-sm font-semibold text-white hover:bg-daun-tua">Daftar</a>
                    <a href="/masuk" class="mt-2 block rounded-full border border-garis px-4 py-3 text-center text-sm font-semibold text-arang hover:bg-kertas">Masuk</a>
                @endauth
            </div>
        </div>
    </div>
</header>
