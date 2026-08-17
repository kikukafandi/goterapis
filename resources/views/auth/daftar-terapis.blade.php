@extends('layouts.app')
@section('title', 'Daftar Jadi Terapis')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | Tentukan langkah yang dibuka ketika validasi server gagal
    |--------------------------------------------------------------------------
    */
    $initialStep = 1;

    $errorKeys = $errors->keys();

    if (collect($errorKeys)->contains(fn ($key) =>
        in_array($key, ['gender', 'experience_years', 'bio'])
    )) {
        $initialStep = 2;
    }

    if (collect($errorKeys)->contains(fn ($key) =>
        in_array($key, ['province', 'city', 'district'])
    )) {
        $initialStep = 3;
    }

    if (collect($errorKeys)->contains(fn ($key) =>
        in_array($key, [
            'serves_call',
            'serves_place',
            'transport_fee',
            'place_address',
        ])
    )) {
        $initialStep = 4;
    }

    if (collect($errorKeys)->contains(fn ($key) =>
        str_starts_with($key, 'services') ||
        str_starts_with($key, 'price') ||
        str_starts_with($key, 'duration')
    )) {
        $initialStep = 5;
    }

    if (collect($errorKeys)->contains(fn ($key) =>
        in_array($key, [
            'ktp',
            'avatar',
            'rekening',
            'sertifikat_pelatihan',
            'sertifikat_pengalaman',
            'stpt',
            'foto_tempat',
        ])
    )) {
        $initialStep = 6;
    }
@endphp

<div
    class="mx-auto max-w-6xl px-4 pb-28 pt-8 sm:pt-10"
    x-data="{
        step: {{ $initialStep }},
        totalSteps: 6,

        call: {{ old('serves_call') ? 'true' : 'false' }},
        place: {{ old('serves_place') ? 'true' : 'false' }},
        gender: @js(old('gender', $akun?->gender)),

        stepError: '',

        stepTitles: [
            'Akun',
            'Data diri',
            'Wilayah',
            'Model layanan',
            'Layanan',
            'Dokumen'
        ],

        validateCurrentStep() {
            this.stepError = '';

            const section = this.$refs['step' + this.step];

            if (!section) {
                return true;
            }

            const inputs = section.querySelectorAll(
                'input, select, textarea'
            );

            for (const input of inputs) {
                if (
                    input.disabled ||
                    input.type === 'hidden' ||
                    input.offsetParent === null
                ) {
                    continue;
                }

                if (!input.checkValidity()) {
                    input.reportValidity();
                    input.focus();

                    return false;
                }
            }

            if (this.step === 4 && !this.call && !this.place) {
                this.stepError = 'Pilih minimal satu model layanan.';

                return false;
            }

            if (this.step === 5) {
                const checkedServices = section.querySelectorAll(
                    'input[name=\'services[]\']:checked'
                );

                if (checkedServices.length === 0) {
                    this.stepError = 'Pilih minimal satu layanan yang kamu kuasai.';

                    return false;
                }

                for (const checkbox of checkedServices) {
                    const id = checkbox.value;

                    const priceInput = section.querySelector(
                        `input[name='price[${id}]']`
                    );

                    const durationInput = section.querySelector(
                        `input[name='duration[${id}]']`
                    );

                    if (!priceInput || !priceInput.value || Number(priceInput.value) <= 0) {
                        this.stepError = 'Isi harga untuk setiap layanan yang dipilih.';

                        priceInput?.focus();

                        return false;
                    }

                    if (
                        !durationInput ||
                        !durationInput.value ||
                        Number(durationInput.value) < 15
                    ) {
                        this.stepError = 'Isi durasi minimal 15 menit untuk setiap layanan.';

                        durationInput?.focus();

                        return false;
                    }
                }
            }

            return true;
        },

        nextStep() {
            if (!this.validateCurrentStep()) {
                return;
            }

            if (this.step < this.totalSteps) {
                this.step++;

                this.scrollToTop();
            }
        },

        previousStep() {
            this.stepError = '';

            if (this.step > 1) {
                this.step--;

                this.scrollToTop();
            }
        },

        goToStep(target) {
            if (target >= this.step) {
                return;
            }

            this.step = target;
            this.stepError = '';

            this.scrollToTop();
        },

        scrollToTop() {
            this.$nextTick(() => {
                this.$refs.formTop.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        }
    }"
>
    <div x-ref="formTop"></div>

    <div class="max-w-2xl">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-daun">Mitra GoTerapis</p>
        <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-arang sm:text-4xl">Gabung jadi terapis GoTerapis</h1>
        <p class="mt-3 text-sm leading-7 text-kabut sm:text-base">
            @if ($akun)
                Akunmu yang sekarang dipakai langsung — tak perlu bikin akun baru. Atur jadwal sendiri, tentukan harga layananmu, dan cairkan penghasilan kapan saja.
            @else
                Atur jadwal sendiri, tentukan harga layananmu, dan cairkan penghasilan kapan saja. Pendaftaran gratis, komisi hanya dipotong dari layanan yang selesai.
            @endif
        </p>
    </div>

    <div class="mt-8 grid items-start gap-7 lg:grid-cols-[18rem_1fr]">
    <aside class="space-y-4 lg:sticky lg:top-24">
    <div class="rounded-card border border-garis bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-kabut">
                    Tahap
                    <span x-text="step"></span>
                    dari
                    <span x-text="totalSteps"></span>
                </p>

                <p
                    class="mt-1 font-display text-lg font-bold text-arang"
                    x-text="stepTitles[step - 1]"
                ></p>
            </div>

            <div class="text-right">
                <span
                    class="text-sm font-bold text-daun"
                    x-text="Math.round((step / totalSteps) * 100) + '%'"
                ></span>
            </div>
        </div>

        <div class="h-2 overflow-hidden rounded-full bg-daun/10">
            <div
                class="h-full rounded-full bg-daun transition-all duration-300"
                :style="`width: ${(step / totalSteps) * 100}%`"
            ></div>
        </div>

        {{-- Tab desktop --}}
        <div class="mt-5 hidden grid-cols-6 gap-2 sm:grid">
            <template x-for="number in totalSteps" :key="number">
                <button
                    type="button"
                    @click="goToStep(number)"
                    class="group flex min-w-0 flex-col items-center gap-2"
                    :class="number < step ? 'cursor-pointer' : 'cursor-default'"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full border text-sm font-bold transition-colors"
                        :class="{
                            'border-daun bg-daun text-white': number === step,
                            'border-daun bg-daun/10 text-daun': number < step,
                            'border-garis bg-white text-kabut': number > step
                        }"
                    >
                        <span x-show="number >= step" x-text="number"></span>

                        <svg
                            x-show="number < step"
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 10.5 8 14l8-8"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>

                    <span
                        class="truncate text-[11px] font-semibold"
                        :class="number <= step ? 'text-arang' : 'text-kabut'"
                        x-text="stepTitles[number - 1]"
                    ></span>
                </button>
            </template>
        </div>

        <div class="mt-4 flex gap-1.5 sm:hidden">
            <template x-for="number in totalSteps" :key="number">
                <button
                    type="button"
                    @click="goToStep(number)"
                    class="h-2 flex-1 rounded-full transition-colors"
                    :class="number <= step ? 'bg-daun' : 'bg-garis'"
                    :aria-label="`Buka tahap ${number}`"
                ></button>
            </template>
        </div>
    </div>
    <div class="rounded-card border border-daun/20 bg-daun-muda p-5">
        <p class="text-sm font-bold text-daun-tua">Berapa yang kamu terima?</p>
        <p class="mt-2 text-xs leading-6 text-daun-tua/80">Dari layanan Rp95.000, setelah komisi platform 15%, Rp80.750 masuk ke saldomu. Biaya transport diterima penuh.</p>
    </div>
    </aside>

    <div class="min-w-0">
    {{-- Server errors --}}
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe">
            <p class="font-semibold">
                Periksa kembali isian berikut:
            </p>

            <ul class="mt-1 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Error per langkah --}}
    <div
        x-show="stepError"
        x-cloak
        class="mt-5 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe"
    >
        <p class="font-semibold" x-text="stepError"></p>
    </div>

    <form
        method="post"
        action="/daftar-terapis"
        enctype="multipart/form-data"
        class="mt-5"
    >
        @csrf

        {{-- Tahap 1: Akun --}}
        <section
            x-ref="step1"
            x-show="step === 1"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 1
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    {{ $akun ? 'Akun yang dipakai' : 'Buat akun terapis' }}
                </h2>

                <p class="mt-1 text-sm text-kabut">
                    {{ $akun
                        ? 'Kamu tetap memakai akun ini — riwayat pesananmu sebagai pelanggan tidak hilang.'
                        : 'Data ini digunakan untuk masuk dan menerima informasi pesanan.' }}
                </p>
            </div>

            @if ($akun)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-3 rounded-2xl border border-garis bg-kertas p-4 sm:col-span-2">
                        <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-full bg-daun-muda text-sm font-bold text-daun">
                            @if ($akun->avatarUrl())
                                <img src="{{ $akun->avatarUrl() }}" alt="{{ $akun->name }}" class="h-full w-full object-cover">
                            @else
                                {{ Str::upper(Str::substr($akun->name, 0, 2)) }}
                            @endif
                        </span>
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span class="truncate text-sm font-bold text-arang">{{ $akun->name }}</span>
                            <span class="truncate text-xs text-kabut">{{ $akun->email }}</span>
                        </span>
                    </div>

                    <x-field
                        name="phone"
                        label="Nomor WhatsApp"
                        type="tel"
                        required
                        :value="$akun->phone"
                        placeholder="08xxxxxxxxxx"
                        class="sm:col-span-2"
                    />

                    <p class="-mt-2 text-xs leading-5 text-kabut sm:col-span-2">
                        Nomor ini dipakai pelanggan untuk menghubungimu. Kalau diganti, kamu perlu verifikasi ulang lewat kode WhatsApp.
                    </p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field
                        name="name"
                        label="Nama lengkap"
                        required
                        placeholder="Nama sesuai KTP"
                    />

                    <x-field
                        name="phone"
                        label="Nomor telepon"
                        type="tel"
                        required
                        placeholder="08xxxxxxxxxx"
                    />

                    <x-field
                        name="email"
                        label="Email"
                        type="email"
                        required
                        placeholder="nama@email.com"
                        class="sm:col-span-2"
                    />

                    <x-field
                        name="password"
                        label="Kata sandi"
                        type="password"
                        required
                        placeholder="Minimal 8 karakter"
                    />

                    <x-field
                        name="password_confirmation"
                        label="Ulangi kata sandi"
                        type="password"
                        required
                        placeholder="••••••••"
                    />
                </div>
            @endif
        </section>

        {{-- Tahap 2: Data diri --}}
        <section
            x-ref="step2"
            x-show="step === 2"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 2
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    Data diri
                </h2>

                <p class="mt-1 text-sm text-kabut">
                    Ceritakan sedikit tentang pengalaman dan keahlianmu.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-arang">
                        Jenis kelamin
                        <span class="text-jahe">*</span>
                    </label>

                    @if ($akun?->gender)
                        {{-- Ikut jenis kelamin akun: terapis hanya melayani pelanggan sesama jenis. --}}
                        <p class="rounded-xl border border-garis bg-kertas px-3 py-2.5 text-sm font-semibold text-arang">
                            {{ ucfirst($akun->gender) }}
                        </p>

                        <p class="mt-1.5 text-xs leading-5 text-kabut">
                            Mengikuti jenis kelamin akunmu. Hubungi dukungan bila perlu diubah.
                        </p>
                    @else
                        <select
                            name="gender"
                            x-model="gender"
                            required
                            class="w-full appearance-none rounded-xl border border-garis bg-white px-3 py-2.5 text-sm outline-none transition-colors focus:border-daun"
                        >
                            <option
                                value=""
                                disabled
                                @selected(! old('gender'))
                            >
                                Pilih jenis kelamin
                            </option>

                            <option
                                value="pria"
                                @selected(old('gender') === 'pria')
                            >
                                Pria
                            </option>

                            <option
                                value="wanita"
                                @selected(old('gender') === 'wanita')
                            >
                                Wanita
                            </option>
                        </select>
                    @endif
                </div>

                <x-field
                    name="experience_years"
                    label="Pengalaman (tahun)"
                    type="number"
                    required
                    value="0"
                    min="0"
                    max="70"
                />

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-arang">
                        Bio singkat
                        <span class="font-normal text-kabut">(opsional)</span>
                    </label>

                    <textarea
                        name="bio"
                        rows="4"
                        maxlength="1000"
                        class="w-full resize-none rounded-xl border border-garis bg-white px-3 py-2.5 text-sm outline-none transition-colors focus:border-daun"
                        placeholder="Ceritakan keahlian, pengalaman, dan gaya terapimu."
                    >{{ old('bio') }}</textarea>
                </div>
            </div>
        </section>

        {{-- Tahap 3: Wilayah --}}
        <section
            x-ref="step3"
            x-show="step === 3"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 3
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    Wilayah layanan
                </h2>

                <p class="mt-1 text-sm text-kabut">
                    Tentukan wilayah utama tempat kamu menerima pelanggan.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field
                    name="province"
                    label="Provinsi"
                    required
                    placeholder="Contoh: Jawa Timur"
                />

                <x-field
                    name="city"
                    label="Kota/Kabupaten"
                    required
                    placeholder="Contoh: Lamongan"
                />

                <x-field
                    name="district"
                    label="Kecamatan"
                    placeholder="Contoh: Lamongan"
                    class="sm:col-span-2"
                />
            </div>
        </section>

        {{-- Tahap 4: Model layanan --}}
        <section
            x-ref="step4"
            x-show="step === 4"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 4
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    Model layanan
                </h2>

                <p class="mt-1 text-sm text-kabut">
                    Pilih cara kamu menerima pelanggan. Kamu bisa memilih keduanya.
                </p>
            </div>

            <div class="space-y-4">
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-colors"
                    :class="call
                        ? 'border-daun bg-daun/5'
                        : 'border-garis hover:border-daun/50'"
                >
                    <input
                        type="checkbox"
                        name="serves_call"
                        value="1"
                        x-model="call"
                        @checked(old('serves_call'))
                        class="mt-1 rounded border-garis text-daun focus:ring-daun"
                    >

                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-arang">
                            Terapis panggilan
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-kabut">
                            Kamu datang langsung ke rumah atau lokasi pelanggan.
                        </span>
                    </span>

                    <span
                        x-show="call"
                        class="rounded-full bg-daun/10 px-2.5 py-1 text-[11px] font-bold text-daun"
                    >
                        Dipilih
                    </span>
                </label>

                <div
                    x-show="call"
                    x-collapse
                    class="rounded-2xl bg-latar p-4"
                >
                    <x-field
                        name="transport_fee"
                        label="Biaya transport"
                        type="number"
                        min="0"
                        value="0"
                        placeholder="Contoh: 20000"
                    />
                </div>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-colors"
                    :class="place
                        ? 'border-daun bg-daun/5'
                        : 'border-garis hover:border-daun/50'"
                >
                    <input
                        type="checkbox"
                        name="serves_place"
                        value="1"
                        x-model="place"
                        @checked(old('serves_place'))
                        class="mt-1 rounded border-garis text-daun focus:ring-daun"
                    >

                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-arang">
                            Di tempat praktik
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-kabut">
                            Pelanggan datang ke alamat tempat praktikmu.
                        </span>
                    </span>

                    <span
                        x-show="place"
                        class="rounded-full bg-daun/10 px-2.5 py-1 text-[11px] font-bold text-daun"
                    >
                        Dipilih
                    </span>
                </label>

                <div
                    x-show="place"
                    x-collapse
                    class="rounded-2xl bg-latar p-4"
                >
                    <x-field
                        name="place_address"
                        label="Alamat tempat praktik"
                        placeholder="Masukkan alamat lengkap"
                    />
                </div>
            </div>
        </section>

        {{-- Tahap 5: Layanan --}}
        <section
            x-ref="step5"
            x-show="step === 5"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 5
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    Layanan dan harga
                </h2>

                <p class="mt-1 text-sm leading-6 text-kabut">
                    Centang layanan yang kamu kuasai, lalu isi harga dan durasinya.
                </p>
            </div>

            @php
                $oldServices = collect(old('services', []))
                    ->map(fn ($id) => (int) $id);
            @endphp

            <div class="space-y-7">
                @foreach ($services as $category => $items)
                    <div>
                        <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-kabut">
                            {{ $categoryLabels[$category] ?? $category }}
                        </h3>

                        <div class="space-y-3">
                            @foreach ($items as $service)
                                <div
                                    x-data="{
                                        selected: {{ $oldServices->contains($service->id) ? 'true' : 'false' }}
                                    }"
                                    x-show="! @js($service->allowed_gender) || gender === @js($service->allowed_gender)"
                                    class="rounded-2xl border p-4 transition-colors"
                                    :class="selected
                                        ? 'border-daun bg-daun/5'
                                        : 'border-garis'"
                                >
                                    <label class="flex cursor-pointer items-center gap-3">
                                        <input
                                            type="checkbox"
                                            name="services[]"
                                            value="{{ $service->id }}"
                                            x-model="selected"
                                            @checked($oldServices->contains($service->id))
                                            class="rounded border-garis text-daun focus:ring-daun"
                                        >

                                        <span class="flex-1 text-sm font-bold text-arang">
                                            {{ $service->name }}
                                        </span>

                                        <span
                                            x-show="selected"
                                            class="rounded-full bg-daun/10 px-2.5 py-1 text-[11px] font-bold text-daun"
                                        >
                                            Dipilih
                                        </span>
                                    </label>

                                    <div
                                        x-show="selected"
                                        x-collapse
                                        class="mt-4 grid gap-3 border-t border-garis pt-4 sm:grid-cols-2"
                                    >
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold text-arang">
                                                Harga layanan
                                            </label>

                                            <div class="flex items-center rounded-xl border border-garis bg-white px-3">
                                                <span class="text-sm text-kabut">
                                                    Rp
                                                </span>

                                                <input
                                                    type="number"
                                                    name="price[{{ $service->id }}]"
                                                    min="0"
                                                    value="{{ old("price.$service->id") }}"
                                                    placeholder="100000"
                                                    class="min-w-0 flex-1 bg-transparent px-2 py-2.5 text-sm outline-none"
                                                >
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold text-arang">
                                                Durasi
                                            </label>

                                            <div class="flex items-center rounded-xl border border-garis bg-white px-3">
                                                <input
                                                    type="number"
                                                    name="duration[{{ $service->id }}]"
                                                    min="15"
                                                    max="480"
                                                    step="15"
                                                    value="{{ old("duration.$service->id", 60) }}"
                                                    class="min-w-0 flex-1 bg-transparent py-2.5 text-sm outline-none"
                                                >

                                                <span class="text-xs text-kabut">
                                                    menit
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Tahap 6: Dokumen --}}
        <section
            x-ref="step6"
            x-show="step === 6"
            x-cloak
            class="rounded-card border border-garis bg-white p-5 sm:p-6"
        >
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-daun">
                    Langkah 6
                </p>

                <h2 class="mt-1 font-display text-xl font-bold text-arang">
                    Dokumen pendukung
                </h2>

                <p class="mt-1 text-sm leading-6 text-kabut">
                    Unggah JPG, PNG, atau PDF maksimal 4 MB. Sertifikat dan
                    STPT bersifat opsional, tetapi dapat meningkatkan status
                    verifikasimu.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-file-field
                    name="ktp"
                    label="Foto KTP"
                    required
                    accept="image/*"
                />

                <x-file-field
                    name="avatar"
                    label="Foto profil (wajah)"
                    required
                    accept="image/*"
                />

                <x-file-field
                    name="rekening"
                    label="Foto buku rekening"
                    accept="image/*"
                />

                <x-file-field
                    name="sertifikat_pelatihan"
                    label="Sertifikat pelatihan"
                    accept="image/*,.pdf"
                />

                <x-file-field
                    name="sertifikat_pengalaman"
                    label="Surat/sertifikat pengalaman"
                    accept="image/*,.pdf"
                />

                <x-file-field
                    name="stpt"
                    label="STPT bila ada"
                    accept="image/*,.pdf"
                />

                <div
                    x-show="place"
                    x-cloak
                    class="sm:col-span-2"
                >
                    <x-file-field
                        name="foto_tempat"
                        label="Foto tempat praktik"
                        accept="image/*"
                    />
                </div>
            </div>

            <label class="mt-5 flex items-start gap-2 rounded-2xl border border-garis p-4 text-xs leading-5 text-kabut">
                <input type="checkbox" name="legal_accepted" value="1" required @checked(old('legal_accepted')) class="mt-1 rounded border-garis text-daun focus:ring-daun">
                <span>Saya menyetujui <a href="{{ route('legal.show', 'syarat-ketentuan') }}" target="_blank" class="font-semibold text-daun hover:underline">Syarat dan Ketentuan</a>, <a href="{{ route('legal.show', 'kebijakan-privasi') }}" target="_blank" class="font-semibold text-daun hover:underline">Kebijakan Privasi</a>, dan aturan komunitas GoTerapis.</span>
            </label>

            <div class="mt-5 rounded-2xl bg-daun/5 p-4">
                <p class="text-sm font-semibold text-arang">
                    Data sudah hampir selesai
                </p>

                <p class="mt-1 text-xs leading-5 text-kabut">
                    Pastikan dokumen terlihat jelas dan informasi yang diberikan
                    sesuai dengan identitasmu.
                </p>
            </div>
        </section>

        {{-- Navigasi --}}
        <div class="mt-5 flex items-center gap-3">
            <button
                type="button"
                x-show="step > 1"
                @click="previousStep()"
                class="flex-1 rounded-full border border-garis bg-white px-4 py-3 text-sm font-semibold text-arang transition-colors hover:border-daun hover:text-daun"
            >
                Kembali
            </button>

            <button
                type="button"
                x-show="step < totalSteps"
                @click="nextStep()"
                class="flex-1 rounded-full bg-daun px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua"
            >
                Lanjut
            </button>

            <button
                type="submit"
                x-show="step === totalSteps"
                @click="
                    if (!validateCurrentStep()) {
                        $event.preventDefault();
                    }
                "
                class="flex-1 rounded-full bg-daun px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-daun-tua"
            >
                Kirim pendaftaran
            </button>
        </div>

        @unless ($akun)
            <p class="mt-5 text-center text-xs text-kabut">
                Sudah punya akun terapis?

                <a
                    href="{{ route('login') }}"
                    class="font-semibold text-daun hover:underline"
                >
                    Masuk
                </a>
            </p>
        @endunless
    </form>
    </div>
    </div>
</div>
@endsection
