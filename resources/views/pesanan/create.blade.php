@extends('layouts.app')
@section('title', 'Pesan '.$therapist->user->name)

@php
    $serviceFee = (int) config('goterapis.service_fee');
    $transportFee = $therapist->serves_call ? (int) $therapist->transport_fee : 0;
    $services = $therapist->services->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'price' => (int) $s->pivot->price,
        'duration' => (int) $s->pivot->duration_min,
    ])->values();
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 pb-28 pt-6"
     x-data="{
        serviceId: @js(old('service_id', optional($services->first())['id'])),
        model: @js(old('model', $therapist->serves_call ? 'panggilan' : 'tempat')),
        services: @js($services),
        serviceFee: {{ $serviceFee }},
        transportFee: {{ $transportFee }},
        date: @js(old('scheduled_at') ? substr(old('scheduled_at'), 0, 10) : now()->addDay()->format('Y-m-d')),
        selectedSlot: @js(old('scheduled_at')),
        slots: [], loadingSlots: false, slotError: '',
        async loadSlots() {
            if (! this.date || ! this.serviceId) return;
            this.loadingSlots = true; this.slotError = '';
            try {
                const query = new URLSearchParams({ date: this.date, service_id: this.serviceId });
                const response = await fetch(@js(route('pesan.availability', $therapist)) + '?' + query);
                if (! response.ok) throw new Error();
                this.slots = (await response.json()).slots;
                if (! this.slots.some(slot => slot.value === this.selectedSlot)) this.selectedSlot = '';
            } catch { this.slots = []; this.slotError = 'Jadwal belum dapat dimuat. Silakan coba lagi.' }
            this.loadingSlots = false;
        },
        get price() { return (this.services.find(s => s.id == this.serviceId) || {}).price || 0 },
        get transport() { return this.model === 'panggilan' ? this.transportFee : 0 },
        get total() { return this.price + this.transport + this.serviceFee },
        rupiah(n) { return 'Rp' + (n || 0).toLocaleString('id-ID') },
     }" x-init="loadSlots()" x-effect="serviceId; loadSlots()">
    <a href="{{ route('terapis.show', $therapist) }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">← Kembali ke profil</a>

    <h1 class="font-display text-2xl font-bold text-arang">Atur layanan dan jadwal</h1>
    <p class="mt-1 text-sm text-kabut">dengan {{ $therapist->user->name }} · {{ $therapist->city }}</p>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('pesanan.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="therapist_profile_id" value="{{ $therapist->id }}">

        {{-- Layanan --}}
        <div class="rounded-card border border-garis bg-white p-5">
            <label class="block text-sm font-semibold text-arang">Pilih layanan</label>
            <div class="mt-3 space-y-2">
                @foreach ($services as $s)
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-garis px-4 py-3 transition-colors"
                           :class="serviceId == {{ $s['id'] }} ? 'border-daun bg-daun/5' : 'hover:border-daun'">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="service_id" value="{{ $s['id'] }}" x-model.number="serviceId"
                                   class="text-daun focus:ring-daun">
                            <span>
                                <span class="block text-sm font-semibold text-arang">{{ $s['name'] }}</span>
                                <span class="block text-xs text-kabut">{{ $s['duration'] }} menit</span>
                            </span>
                        </span>
                        <span class="shrink-0 text-sm font-bold text-arang">Rp{{ number_format($s['price'], 0, ',', '.') }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Model layanan --}}
        <div class="rounded-card border border-garis bg-white p-5">
            <label class="block text-sm font-semibold text-arang">Model layanan</label>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($therapist->serves_call)
                    <label class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                           :class="model === 'panggilan' ? 'border-daun bg-daun text-white' : 'border-garis text-arang hover:border-daun'">
                        <input type="radio" name="model" value="panggilan" x-model="model" class="hidden"> Panggilan ke rumah
                    </label>
                @endif
                @if ($therapist->serves_place)
                    <label class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                           :class="model === 'tempat' ? 'border-daun bg-daun text-white' : 'border-garis text-arang hover:border-daun'">
                        <input type="radio" name="model" value="tempat" x-model="model" class="hidden"> Datang ke tempat praktik
                    </label>
                @endif
            </div>

            {{-- Alamat + titik lokasi (hanya untuk panggilan) --}}
            <div x-show="model === 'panggilan'" x-cloak class="mt-4"
                 x-data="{
                    lat: @js(old('lat')), lng: @js(old('lng')), acc: @js(old('acc')), status: @js(old('lat') ? 'ok' : 'idle'),
                    ambil() {
                        if (! navigator.geolocation) { this.status = 'error'; return }
                        this.status = 'loading';
                        navigator.geolocation.getCurrentPosition(
                            p => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7);
                                   this.acc = Math.round(p.coords.accuracy); this.status = 'ok' },
                            () => { this.status = 'error' },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    }
                  }">
                <label class="block text-sm font-semibold text-arang">Alamat lengkap</label>
                <textarea name="address" rows="2" placeholder="Nama jalan, nomor rumah, patokan…"
                          class="mt-2 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none placeholder:text-kabut focus:border-daun">{{ old('address') }}</textarea>

                <input type="hidden" name="lat" :value="lat">
                <input type="hidden" name="lng" :value="lng">
                <input type="hidden" name="acc" :value="acc">
                <button type="button" @click="ambil()"
                        class="mt-3 inline-flex items-center gap-2 rounded-full border border-garis px-4 py-2 text-sm font-semibold text-arang transition-colors hover:border-daun">
                    <x-icon name="pin" class="h-4 w-4 text-daun" />
                    <span x-text="status === 'ok' ? 'Perbarui titik lokasi' : 'Gunakan lokasi saya'"></span>
                </button>
                <p class="mt-2 text-xs" x-show="status === 'loading'">Mengambil lokasi…</p>
                <p class="mt-2 text-xs text-daun-tua" x-show="status === 'ok'" x-cloak>✓ Titik lokasi terekam. Terapis wajib berada di titik ini untuk memulai layanan.</p>
                <p class="mt-2 text-xs text-jahe" x-show="status === 'error'" x-cloak>Gagal mengambil lokasi. Aktifkan izin lokasi, atau lanjut tanpa titik.</p>
            </div>
        </div>

        {{-- Jadwal --}}
        <div class="rounded-card border border-garis bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <label for="booking-date" class="block text-sm font-semibold text-arang">Tanggal layanan</label>
                    <p class="mt-1 text-xs text-kabut">Slot yang tampil sudah memperhitungkan pesanan lain · WIB</p>
                </div>
                <x-icon name="calendar" class="h-5 w-5 text-daun" />
            </div>
            <input id="booking-date" type="date" x-model="date" @change="loadSlots()"
                   min="{{ now()->format('Y-m-d') }}"
                   class="mt-3 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none focus:border-daun">
            <input type="hidden" name="scheduled_at" :value="selectedSlot">

            <fieldset class="mt-4">
                <legend class="text-sm font-semibold text-arang">Jam tersedia</legend>
                <p x-show="loadingSlots" class="mt-3 text-sm text-kabut">Memeriksa jadwal terapis…</p>
                <p x-show="slotError" x-text="slotError" class="mt-3 text-sm text-jahe"></p>
                <p x-show="! loadingSlots && ! slotError && slots.length === 0" class="mt-3 rounded-xl bg-kertas px-4 py-3 text-sm text-kabut">Belum ada slot tersedia pada tanggal ini.</p>
                <div x-show="! loadingSlots && slots.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    <template x-for="slot in slots" :key="slot.value">
                        <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-center text-sm font-semibold transition-colors"
                               :class="selectedSlot === slot.value ? 'border-daun bg-daun text-white' : 'border-garis text-arang hover:border-daun'">
                            <input type="radio" class="sr-only" :value="slot.value" x-model="selectedSlot">
                            <span x-text="slot.label"></span>
                        </label>
                    </template>
                </div>
            </fieldset>

            <label class="mt-5 block text-sm font-semibold text-arang">Catatan (opsional)</label>
            <textarea name="notes" rows="2" placeholder="Keluhan atau permintaan khusus…"
                      class="mt-2 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none placeholder:text-kabut focus:border-daun">{{ old('notes') }}</textarea>
        </div>

        {{-- Rincian biaya --}}
        <div class="rounded-card border border-garis bg-white p-5">
            <h2 class="text-sm font-semibold text-arang">Rincian biaya</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-kabut">Harga layanan</dt><dd class="font-semibold text-arang" x-text="rupiah(price)"></dd></div>
                <div class="flex justify-between" x-show="transport > 0"><dt class="text-kabut">Biaya transport</dt><dd class="font-semibold text-arang" x-text="rupiah(transport)"></dd></div>
                <div class="flex justify-between"><dt class="text-kabut">Biaya layanan</dt><dd class="font-semibold text-arang" x-text="rupiah(serviceFee)"></dd></div>
                <div class="flex justify-between border-t border-garis pt-2"><dt class="font-bold text-arang">Total</dt><dd class="font-bold text-daun" x-text="rupiah(total)"></dd></div>
            </dl>
        </div>

        <button type="submit" :disabled="! selectedSlot || loadingSlots"
                class="w-full rounded-full bg-daun px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua disabled:cursor-not-allowed disabled:opacity-50">
            Buat pesanan
        </button>
        <p class="text-center text-xs text-kabut">Pembayaran akan tersedia pada langkah berikutnya.</p>
    </form>
</div>
@endsection
