@php
    $therapistShell = auth()->user()?->isTherapist() && request()->routeIs('mitra.*', 'chat', 'notifications.*');
    $tabs = $therapistShell ? [
        ['label' => 'Beranda', 'href' => route('mitra.dashboard'), 'active' => request()->routeIs('mitra.dashboard'), 'icon' => 'home'],
        ['label' => 'Pesanan', 'href' => route('mitra.pesanan'), 'active' => request()->routeIs('mitra.pesanan', 'mitra.pesanan.*'), 'icon' => 'clipboard'],
        ['label' => 'Pesan', 'href' => route('chat'), 'active' => request()->routeIs('chat'), 'icon' => 'chat'],
        ['label' => 'Saldo', 'href' => route('mitra.saldo'), 'active' => request()->routeIs('mitra.saldo'), 'icon' => 'wallet'],
        ['label' => 'Profil', 'href' => route('mitra.profil.edit'), 'active' => request()->routeIs('mitra.profil.*'), 'icon' => 'user'],
    ] : [
        ['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'home'],
        ['label' => 'Cari', 'href' => route('cari'), 'active' => request()->is('cari*'), 'icon' => 'search'],
        ['label' => 'Pesanan', 'href' => route('pesanan.index'), 'active' => request()->is('pesanan*'), 'icon' => 'clipboard'],
        ['label' => 'Pesan', 'href' => route('chat'), 'active' => request()->routeIs('chat'), 'icon' => 'chat'],
        ['label' => auth()->check() ? 'Akun' : 'Masuk', 'href' => auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('akun')) : route('login'), 'active' => request()->is('akun*', 'masuk'), 'icon' => 'user'],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-garis bg-white/95 backdrop-blur md:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
    <ul class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($tabs as $tab)
            <li><a href="{{ $tab['href'] }}" @if ($tab['active']) aria-current="page" @endif class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold {{ $tab['active'] ? 'text-daun' : 'text-kabut' }}"><x-icon :name="$tab['icon']" class="h-6 w-6" />{{ $tab['label'] }}</a></li>
        @endforeach
    </ul>
</nav>
