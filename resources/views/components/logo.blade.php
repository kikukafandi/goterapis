@props([
    'variant' => 'mark',    // 'mark' (ikon saja) atau 'full' (ikon + wordmark)
])

<img src="{{ asset('images/brand/logo-'.$variant.'.png') }}" alt="GoTerapis"
     {{ $attributes->merge(['class' => 'block w-auto shrink-0']) }}>
