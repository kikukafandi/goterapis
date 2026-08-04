@auth
<div class="fixed right-4 top-20 z-50 w-[min(22rem,calc(100vw-2rem))] space-y-2" aria-live="polite" aria-atomic="false"
     x-data="{
        items: [],
        add(notification) {
            const item = { ...notification, toastId: crypto.randomUUID() };
            this.items.unshift(item);
            this.items = this.items.slice(0, 3);
            setTimeout(() => this.close(item.toastId), 5000);
        },
        close(id) { this.items = this.items.filter(item => item.toastId !== id) }
     }"
     x-init="window.Echo?.private('App.Models.User.{{ auth()->id() }}').notification(notification => add(notification))">
    <template x-for="item in items" :key="item.toastId">
        <div x-transition class="rounded-card border border-garis bg-white p-4 shadow-lg">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-daun-muda text-daun"><x-icon name="bell" class="h-4 w-4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-arang" x-text="item.message"></p>
                    <a :href="item.url" class="mt-2 inline-block text-xs font-semibold text-daun hover:underline">Buka pesanan</a>
                </div>
                <button type="button" @click="close(item.toastId)" aria-label="Tutup notifikasi" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-kabut hover:bg-kertas"><x-icon name="close" class="h-4 w-4" /></button>
            </div>
        </div>
    </template>
</div>
@endauth
