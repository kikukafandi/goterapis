@props(['order', 'messages'])

<section aria-labelledby="judul-chat" class="rounded-card border border-garis bg-white p-5 sm:p-6"
         x-data="{
            messages: @js($messages->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'created_at' => $message->created_at->toISOString(),
            ])),
            body: '', sending: false, error: '', userId: {{ auth()->id() }},
            init() {
                this.scroll();
                window.Echo?.private('orders.{{ $order->id }}').listen('ChatMessageSent', message => this.add(message));
            },
            add(message) {
                if (! this.messages.some(item => String(item.id) === String(message.id))) {
                    this.messages.push(message); this.scroll();
                }
            },
            scroll() { this.$nextTick(() => this.$refs.list.scrollTop = this.$refs.list.scrollHeight) },
            async send() {
                const body = this.body.trim();
                if (! body || this.sending) return;
                this.sending = true; this.error = '';
                try {
                    const response = await fetch('{{ route('pesanan.chat.store', $order, absolute: false) }}', {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: JSON.stringify({body}),
                    });
                    if (! response.ok) throw new Error();
                    this.add(await response.json()); this.body = '';
                } catch (_) { this.error = 'Pesan belum terkirim. Periksa koneksi, lalu coba lagi.' }
                finally { this.sending = false; this.$nextTick(() => this.$refs.input.focus()) }
            }
         }">
    <div class="flex flex-wrap items-center gap-3 border-b border-garis pb-4">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-daun-muda text-daun-tua"><x-icon name="chat" class="h-5 w-5" /></span>
        <div class="min-w-0 flex-1"><h2 id="judul-chat" class="font-display text-lg font-bold text-arang">Percakapan pesanan</h2><p class="text-xs text-kabut">Koordinasikan jadwal dan layanan di sini.</p></div>
        <div class="w-full pl-13 sm:w-auto sm:pl-0"><x-order-report :$order source="chat" /></div>
    </div>

    <div x-ref="list" role="log" aria-live="polite" class="mt-4 h-80 space-y-3 overflow-y-auto rounded-xl bg-kertas p-3">
        <template x-if="messages.length === 0"><p class="grid h-full place-items-center text-center text-sm text-kabut">Belum ada pesan. Mulai percakapan dengan ramah.</p></template>
        <template x-for="message in messages" :key="message.id">
            <div class="flex" :class="message.sender_id === userId ? 'justify-end' : 'justify-start'">
                <div class="max-w-[85%] rounded-xl px-3.5 py-2.5" :class="message.sender_id === userId ? 'bg-daun text-white' : 'border border-garis bg-white text-arang'">
                    <p class="text-xs font-semibold opacity-75" x-text="message.sender_name"></p>
                    <p class="mt-0.5 whitespace-pre-wrap break-words text-sm" x-text="message.body"></p>
                    <time class="mt-1 block text-[11px] opacity-65" :datetime="message.created_at" x-text="new Intl.DateTimeFormat('id-ID', {hour: '2-digit', minute: '2-digit'}).format(new Date(message.created_at))"></time>
                </div>
            </div>
        </template>
    </div>

    <form class="mt-3" @submit.prevent="send">
        <label for="chat-body-{{ $order->id }}" class="sr-only">Tulis pesan</label>
        <div class="flex items-end gap-2">
            <textarea id="chat-body-{{ $order->id }}" x-ref="input" x-model="body" maxlength="1000" rows="2" required placeholder="Tulis pesan…" @keydown.enter.exact.prevent="send"
                      class="min-h-12 flex-1 resize-none rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none placeholder:text-kabut focus:border-daun"></textarea>
            <button type="submit" :disabled="sending || !body.trim()" class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-daun text-white transition-colors hover:bg-daun-tua disabled:opacity-50" aria-label="Kirim pesan">
                <x-icon name="arrow-right" class="h-5 w-5" />
            </button>
        </div>
        <p x-show="error" x-text="error" role="alert" class="mt-2 text-sm text-jahe"></p>
    </form>
</section>
