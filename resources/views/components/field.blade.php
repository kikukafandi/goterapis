@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
    'value' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class') }}>
    <label class="mb-1.5 block text-sm font-semibold text-arang">
        {{ $label }}@if ($required)<span class="text-jahe"> *</span>@endif
    </label>
    <input name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
           placeholder="{{ $placeholder }}"
           {{ $attributes->except('class')->merge(['class' => 'w-full rounded-xl border bg-white px-3 py-2.5 text-sm outline-none focus:border-daun '.($hasError ? 'border-jahe' : 'border-garis')]) }}>
</div>
