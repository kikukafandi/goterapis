@props(['slug', 'src' => null])

@php
    // Urutan gambar: ikon unggahan admin, lalu file bawaan di
    // public/images/kategori/<slug>.(webp|svg|png), terakhir SVG fallback di bawah.
    // ponytail: file_exists per-render; cukup untuk segelintir kategori.
    $img = $src;
    foreach ($img ? [] : ['webp', 'svg', 'png'] as $ext) {
        if (file_exists(public_path("images/kategori/{$slug}.{$ext}"))) {
            $img = asset("images/kategori/{$slug}.{$ext}");
            break;
        }
    }

    $fallback = [
        'pijat'    => '<path d="M9 11V6a1.5 1.5 0 0 1 3 0v4M12 10V5a1.5 1.5 0 0 1 3 0v5M15 10V7a1.5 1.5 0 0 1 3 0v6a6 6 0 0 1-6 6h-2a4 4 0 0 1-3-1.5l-3-3.5a1.6 1.6 0 0 1 2.4-2L8 14"/>',
        'bekam'    => '<path d="M6 4h12v4a6 6 0 0 1-12 0z"/><path d="M12 14v6"/>',
        'kretek'   => '<path d="M12 3v18"/><path d="m8 6 4-3 4 3M8 18l4 3 4-3"/>',
        'lainnya'  => '<path d="M20 4C10 4 4 10 4 20c8 0 16-6 16-16Z"/><path d="M8 16c3-4 6-6 9-7"/>',
    ];
    $svg = $fallback[$slug] ?? $fallback['lainnya'];
@endphp

@if ($img)
    {{-- Foto kustom mengisi penuh kotak; ukuran diatur oleh <span> pembungkus --}}
    <img src="{{ $img }}" alt="" aria-hidden="true" class="h-full w-full object-cover">
@else
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
         {{ $attributes->merge(['class' => 'h-6 w-6']) }}>
        {!! $svg !!}
    </svg>
@endif
