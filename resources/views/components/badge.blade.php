@props([
    'status',           // slug ('pilihan') atau label ('Terapis Pilihan')
    'size' => 'h-8 w-8',
])

@php
    $labels = \App\Models\TherapistProfile::STATUS_LABELS;

    $badges = [
        'anggota' => 'badge_anggota_kumitas.webp',
        'identitas' => 'badge_identitas_verifikasi.webp',
        'berpengalaman' => 'badge_terapis_berpengalaman.webp',
        'terdaftar' => 'badge_terapis_terverifikasi.webp',
        'pilihan' => 'badge_terapis_pilihan.webp',
    ];

    // Terima slug maupun label.
    $slug = isset($badges[$status]) ? $status : array_search($status, $labels, true);
    $file = $slug ? ($badges[$slug] ?? null) : null;
    $label = $slug ? $labels[$slug] : null;
@endphp

@if ($file)
    <img src="{{ asset('images/badges/'.$file) }}" alt="{{ $label }}" title="{{ $label }}" loading="lazy"
         {{ $attributes->merge(['class' => $size.' inline-block shrink-0 object-contain align-middle']) }}>
@endif
