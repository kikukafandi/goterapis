@php
    $tabs = [
        ['label' => 'Beranda', 'href' => '/', 'active' => request()->is('/'),
         'icon' => 'M3 11.5 12 4l9 7.5M5.5 10v9.5h13V10'],
        ['label' => 'Cari', 'href' => '/cari', 'active' => request()->is('cari*'),
         'icon' => 'M11 4a7 7 0 1 0 4.9 12l4.1 4.1M11 4a7 7 0 0 1 0 14'],
        ['label' => 'Pesanan', 'href' => auth()->user()?->isTherapist() ? route('mitra.pesanan') : route('pesanan.index'), 'active' => request()->is('pesanan*') || request()->is('mitra/pesanan*'),
          'icon' => 'M6 4h9l3 3v13H6zM9 9h6M9 13h6M9 17h4'],
        ['label' => 'Pesan', 'href' => route('chat'), 'active' => request()->routeIs('chat'),
         'icon' => 'M4 5h16v11H9l-5 4z'],
        ['label' => auth()->check() ? 'Akun' : 'Masuk',
         'href' => auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : '/akun') : '/masuk',
         'active' => request()->is('akun*') || request()->is('masuk'),
         'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M5 20c1.2-3.5 4-5 7-5s5.8 1.5 7 5'],
    ];
@endphp
{{-- App-shell: tab bar bawah, tampil di mobile saja --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-garis bg-white/95 backdrop-blur md:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)">
    <ul class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($tabs as $t)
            <li>
                <a href="{{ $t['href'] }}"
                   class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold {{ $t['active'] ? 'text-daun' : 'text-kabut' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="{{ $t['icon'] }}"/>
                    </svg>
                    {{ $t['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
