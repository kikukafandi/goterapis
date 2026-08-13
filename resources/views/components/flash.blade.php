{{-- Pesan kilat dari controller (session success/status). Dipasang sekali di layout. --}}
@php($pesan = session('success') ?? session('status'))

@if ($pesan)
    <div role="status" {{ $attributes->merge(['class' => 'rounded-card border border-daun-garis bg-daun-muda px-4 py-3 text-sm font-medium text-daun-tua']) }}>
        {{ $pesan }}
    </div>
@endif
