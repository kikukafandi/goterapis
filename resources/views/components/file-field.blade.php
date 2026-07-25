@props([
    'name',
    'label',
    'required' => false,
    'accept' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

{{-- Native file input; label sekaligus jadi tombol. Nama file terpilih ditampilkan via Alpine. --}}
<div x-data="{ file: '' }">
    <label class="mb-1.5 block text-sm font-semibold text-arang">
        {{ $label }}@if ($required)<span class="text-jahe"> *</span>@endif
    </label>
    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-dashed px-3 py-2.5 text-sm hover:border-daun {{ $hasError ? 'border-jahe' : 'border-garis' }}">
        <x-icon name="upload" class="h-5 w-5 shrink-0 text-daun" />
        <span class="truncate text-kabut" x-text="file || 'Pilih berkas…'"></span>
        <input type="file" name="{{ $name }}" @required($required) @if ($accept) accept="{{ $accept }}" @endif
               @change="file = $event.target.files[0]?.name ?? ''" class="sr-only">
    </label>
</div>
