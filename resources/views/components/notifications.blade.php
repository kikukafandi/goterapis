@php
    $initialToasts = collect([
        (session('success') ?? session('ok')) ? ['message' => session('success') ?? session('ok'), 'type' => 'success'] : null,
        session('error') ? ['message' => session('error'), 'type' => 'error'] : null,
    ])->filter()->values();
@endphp
<div class="pointer-events-none fixed inset-x-0 top-20 z-[70] flex flex-col items-center gap-2 px-3 sm:items-end sm:px-5"
     aria-live="polite" aria-atomic="false"
     x-data="notifications(@js($initialToasts), @js(auth()->check() ? 'App.Models.User.'.auth()->id() : null))">
    <template x-for="item in items" :key="item.toastId">
        <div x-transition:enter="transition duration-300 ease-out"
             x-transition:enter-start="translate-y-3 opacity-0 sm:translate-x-5 sm:translate-y-0"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition duration-200 ease-in"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="translate-y-2 opacity-0 sm:translate-x-5 sm:translate-y-0"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-[18px] border bg-white p-3.5 shadow-[0_18px_50px_rgba(20,26,19,.16)]"
             :class="item.type === 'error' ? 'border-jahe-garis' : item.type === 'success' ? 'border-daun-garis' : 'border-garis'"
             :role="item.type === 'error' ? 'alert' : 'status'">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                  :class="item.type === 'error' ? 'bg-jahe-muda text-jahe' : item.type === 'success' ? 'bg-daun-muda text-daun-terang' : 'bg-kunyit-muda text-kunyit-tua'">
                <span x-show="item.type === 'success'" class="text-sm font-extrabold">✓</span>
                <span x-show="item.type === 'error'" class="text-sm font-extrabold">!</span>
                <span x-show="item.type === 'notification'"><x-icon name="bell" class="h-4 w-4" /></span>
            </span>
            <span class="min-w-0 flex-1 py-0.5">
                <strong class="block text-xs text-arang" x-text="item.type === 'error' ? 'Belum berhasil' : item.type === 'success' ? 'Berhasil' : 'Kabar baru'"></strong>
                <span class="mt-1 block text-[12px] font-medium leading-relaxed text-kabut" x-text="item.message"></span>
                <a x-show="item.url" :href="item.url" class="mt-2 inline-block text-[11px] font-bold text-daun">Buka</a>
            </span>
            <button type="button" @click="close(item.toastId)" aria-label="Tutup" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-kabut-samar hover:bg-kertas-app"><x-icon name="close" class="h-4 w-4" /></button>
        </div>
    </template>
</div>
