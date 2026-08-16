<div class="flex items-center justify-between">
    <h2 class="font-display text-base font-extrabold text-arang">Filter</h2>
    <a href="{{ route('cari') }}" class="text-xs font-semibold text-kabut-samar hover:text-daun">Reset</a>
</div>

<div class="mt-6">
    <p class="text-xs font-bold text-arang">Kategori</p>
    <div class="mt-3 space-y-1">
        <a href="{{ request()->fullUrlWithQuery(['kategori' => null, 'page' => null]) }}" class="flex items-center gap-2.5 py-2 text-[13px] font-medium text-kabut">
            <span class="grid h-[18px] w-[18px] place-items-center rounded-[5px] border-2 {{ ! $kategori ? 'border-daun-terang bg-daun-terang text-white' : 'border-garis bg-white' }}">@if (! $kategori)<span class="text-[10px] font-extrabold">✓</span>@endif</span>Semua kategori
        </a>
        @foreach ($categories as $slug => $label)
            <a href="{{ request()->fullUrlWithQuery(['kategori' => $slug, 'page' => null]) }}" class="flex items-center gap-2.5 py-2 text-[13px] font-medium text-kabut">
                <span class="grid h-[18px] w-[18px] place-items-center rounded-[5px] border-2 {{ $kategori === $slug ? 'border-daun-terang bg-daun-terang text-white' : 'border-garis bg-white' }}">@if ($kategori === $slug)<span class="text-[10px] font-extrabold">✓</span>@endif</span>{{ $label }}
            </a>
        @endforeach
    </div>
</div>

{{-- Filter gender hilang begitu jenis kelamin pelanggan diketahui: pilihannya sudah terkunci. --}}
<div class="mt-5 border-t border-garis-muda pt-5">
    <p class="text-xs font-bold text-arang">Gender terapis</p>
    @if (auth()->user()?->gender)
        <p class="mt-3 text-[13px] font-medium text-kabut">
            Hanya terapis {{ auth()->user()->gender }} — terapis melayani pelanggan dengan jenis kelamin yang sama.
        </p>
    @else
        <div class="mt-3 space-y-1">
            @foreach (['' => 'Semua', 'pria' => 'Pria', 'wanita' => 'Wanita'] as $value => $label)
                <a href="{{ request()->fullUrlWithQuery(['gender' => $value ?: null, 'page' => null]) }}" class="flex items-center gap-2.5 py-2 text-[13px] font-medium text-kabut">
                    <span class="grid h-[18px] w-[18px] place-items-center rounded-[5px] border-2 {{ ($gender ?: '') === $value ? 'border-daun-terang bg-daun-terang text-white' : 'border-garis bg-white' }}">@if (($gender ?: '') === $value)<span class="text-[10px] font-extrabold">✓</span>@endif</span>{{ $label }}
                </a>
            @endforeach
        </div>
    @endif
</div>

<div class="mt-5 border-t border-garis-muda pt-5">
    <p class="text-xs font-bold text-arang">Model layanan</p>
    <div class="mt-3 space-y-1">
        @foreach (['' => 'Semua', 'panggilan' => 'Panggilan ke rumah', 'tempat' => 'Datang ke tempat praktik'] as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['model' => $value ?: null, 'page' => null]) }}" class="flex items-center gap-2.5 py-2 text-[13px] font-medium text-kabut">
                <span class="grid h-[18px] w-[18px] place-items-center rounded-[5px] border-2 {{ ($model ?: '') === $value ? 'border-daun-terang bg-daun-terang text-white' : 'border-garis bg-white' }}">@if (($model ?: '') === $value)<span class="text-[10px] font-extrabold">✓</span>@endif</span>{{ $label }}
            </a>
        @endforeach
    </div>
</div>
