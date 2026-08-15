@props(['order', 'source' => 'order'])

@php
    // Penolakan server (jendela 24 jam lewat, dana terapis sudah dicairkan) balik sebagai
    // error validasi. Tanpa ini modal tertutup diam-diam dan pelapor mengira laporannya masuk.
    $ditolak = old('source') === $source && ($errors->has('detail') || $errors->has('reason'));
@endphp

<div x-data="{ open: @json($ditolak) }">
    <button type="button" @click="open = true" class="inline-flex items-center gap-2 text-xs font-semibold text-jahe hover:underline">
        <x-icon name="shield" class="h-4 w-4" /> Laporkan perilaku tidak pantas
    </button>

    <div x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-50 grid place-items-end bg-arang/50 p-0 sm:place-items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="judul-laporan-{{ $order->id }}-{{ $source }}">
        <div @click.outside="open = false" class="w-full max-w-lg rounded-t-card border border-garis bg-white p-5 shadow-xl sm:rounded-card sm:p-7">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-jahe-muda text-jahe"><x-icon name="shield" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <h2 id="judul-laporan-{{ $order->id }}-{{ $source }}" class="font-display text-lg font-bold text-arang">Laporkan pelecehan</h2>
                    <p class="mt-1 text-xs leading-relaxed text-kabut">Laporan bersifat rahasia. Bukti pesanan dan percakapan saat ini akan diamankan untuk peninjauan admin.</p>
                </div>
                <button type="button" @click="open = false" class="text-kabut" aria-label="Tutup"><x-icon name="close" class="h-5 w-5" /></button>
            </div>
            <form method="post" action="{{ route('pesanan.reports.store', $order) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="source" value="{{ $source }}">
                @if ($ditolak)
                    <p class="rounded-xl border border-jahe-garis bg-jahe-muda px-4 py-3 text-xs font-semibold leading-relaxed text-jahe">{{ $errors->first('detail') ?: $errors->first('reason') }}</p>
                @endif
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-arang">Jenis kejadian</span>
                    <select name="reason" required class="isian">
                        <option value="pelecehan_seksual">Pelecehan seksual</option>
                        <option value="perilaku_tidak_pantas">Perilaku seksual tidak pantas</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-arang">Ceritakan kejadian</span>
                    <textarea name="detail" required minlength="20" maxlength="5000" rows="6" class="isian resize-y" placeholder="Jelaskan apa yang terjadi, kapan, dan hal penting lain yang perlu ditinjau.">{{ $ditolak ? old('detail') : '' }}</textarea>
                    <span class="mt-1 block text-[11px] text-kabut">20–5.000 karakter</span>
                </label>
                <div class="flex gap-2.5">
                    <button type="button" @click="open = false" class="flex-1 rounded-xl border border-garis px-4 py-3 text-sm font-bold text-arang">Batal</button>
                    <button class="flex-1 rounded-xl bg-jahe px-4 py-3 text-sm font-bold text-white hover:opacity-90">Kirim laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>
