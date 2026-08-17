{{-- Satu baris menu sidebar admin. Dipakai untuk Dashboard maupun isi tiap klaster. --}}
<a href="{{ $m['href'] }}"
   @if ($m['active']) aria-current="page" @endif
   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold {{ $m['active'] ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5' }}">
    <x-icon :name="$m['icon']" class="h-[18px] w-[18px] shrink-0 {{ $m['active'] ? 'text-daun-neon' : 'text-white/40' }}" />
    <span class="flex-1 truncate">{{ $m['label'] }}</span>
    @if (($m['count'] ?? 0) > 0)
        <span class="min-w-5 rounded-full bg-jahe-terang px-1.5 text-center text-[10px] font-bold leading-5 text-white">{{ $m['count'] }}</span>
    @endif
</a>
