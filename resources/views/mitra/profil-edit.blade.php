@extends('layouts.app')
@section('title', 'Edit Profil Terapis')
@section('content')
@php
    $selected = $profile->services->keyBy('id');
    $savedSchedules = $profile->schedules->keyBy('day_of_week');
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp
<div class="relative overflow-hidden bg-daun-terang px-5 pb-6 pt-4 text-white">
    <span class="pointer-events-none absolute -right-12 -top-16 h-44 w-44 rounded-full bg-white/10 lg:-right-20 lg:-top-44 lg:h-96 lg:w-96"></span>
    <div class="mx-auto max-w-3xl">
        <h1 class="font-display text-[22px] font-extrabold">Profil</h1>
        <div class="mt-5 flex items-center gap-4">
            @if ($profile->user->avatarUrl())
                <img src="{{ $profile->user->avatarUrl() }}" alt="" class="h-[66px] w-[66px] shrink-0 rounded-[22px] object-cover">
            @else
                <span class="grid h-[66px] w-[66px] shrink-0 place-items-center rounded-[22px] bg-white/25 text-xl font-extrabold">{{ mb_substr($profile->user->name, 0, 1) }}</span>
            @endif
            <span class="min-w-0 flex-1">
                <span class="block truncate font-display text-lg font-extrabold">{{ $profile->user->name }}</span>
                <span class="mt-1 block text-[11px] font-semibold text-white/85">Terapis {{ ucfirst($profile->verification_status) }} · {{ $profile->city }}</span>
                <a href="{{ route('terapis.show', $profile) }}" class="mt-2 inline-block rounded-full border border-white/40 px-3 py-2 text-[11px] font-bold">Lihat profil publik</a>
            </span>
        </div>
    </div>
</div>
<div class="mx-auto max-w-3xl px-5 pb-28 pt-4">
    <p class="text-xs text-kabut-muda">Nomor WhatsApp hanya dipakai untuk notifikasi dan tidak ditampilkan kepada pelanggan.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-card border border-jahe/30 bg-jahe/10 p-4 text-sm text-arang">Periksa kembali isian yang ditandai.</div>
    @endif

    {{-- Form terpisah agar tombol kirim kode tidak ikut menyimpan profil. --}}
    <form id="kirim-kode-nomor" method="post" action="{{ route('mitra.otp.send') }}" class="hidden">
        @csrf
        <input type="hidden" name="purpose" value="nomor">
    </form>

    <form method="post" action="{{ route('mitra.profil.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
        @csrf @method('PUT')
        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Akun & notifikasi</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Nama lengkap" :value="$profile->user->name" required />
                <x-field name="phone" label="Nomor WhatsApp" :value="$profile->user->phone" required inputmode="tel" />
                <x-field name="email" label="Email" type="email" :value="$profile->user->email" required class="sm:col-span-2" />
                @if ($profile->user->phone_verified_at)
                    {{-- Nomor terverifikasi jadi kunci penarikan saldo, jadi penggantiannya harus disetujui dari nomor lama. --}}
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-arang">Kode verifikasi <span class="font-normal text-kabut-muda">— hanya bila nomor WhatsApp diganti</span></label>
                        <div class="flex gap-2">
                            <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="6 digit"
                                   class="w-full rounded-xl border bg-white px-3 py-2.5 text-sm tracking-widest outline-none focus:border-daun {{ $errors->has('code') ? 'border-jahe' : 'border-garis' }}">
                            <button type="submit" form="kirim-kode-nomor" class="btn-garis shrink-0 text-xs">Kirim kode</button>
                        </div>
                        <span class="mt-1.5 block text-xs text-kabut-muda">Kode dikirim ke nomor lamamu ({{ $profile->user->phone }}).</span>
                        @error('code')<p class="mt-1.5 text-xs font-medium text-jahe">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-arang">Foto profil baru</label><input type="file" name="avatar" accept="image/*" class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm"></div>
            </div>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Profil profesional</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-semibold text-arang">Jenis kelamin</label><select name="gender" class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm"><option value="pria" @selected(old('gender', $profile->gender) === 'pria')>Pria</option><option value="wanita" @selected(old('gender', $profile->gender) === 'wanita')>Wanita</option></select></div>
                <x-field name="experience_years" label="Pengalaman (tahun)" type="number" :value="$profile->experience_years" required min="0" max="70" />
                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-arang">Tentang saya</label><textarea name="bio" rows="5" maxlength="1000" class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm outline-none focus:border-daun">{{ old('bio', $profile->bio) }}</textarea></div>
            </div>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Wilayah & model layanan</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-field name="province" label="Provinsi" :value="$profile->province" required />
                <x-field name="city" label="Kota/kabupaten" :value="$profile->city" required />
                <x-field name="district" label="Kecamatan" :value="$profile->district" />
                <x-field name="transport_fee" label="Biaya transportasi" type="number" :value="$profile->transport_fee" min="0" />
                <label class="flex items-center gap-3 rounded-xl border border-garis p-4 text-sm font-semibold text-arang"><input type="checkbox" name="serves_call" value="1" @checked(old('serves_call', $profile->serves_call)) class="h-4 w-4 accent-daun"> Panggilan ke pelanggan</label>
                <label class="flex items-center gap-3 rounded-xl border border-garis p-4 text-sm font-semibold text-arang"><input type="checkbox" name="serves_place" value="1" @checked(old('serves_place', $profile->serves_place)) class="h-4 w-4 accent-daun"> Di tempat praktik</label>
                <x-field name="place_address" label="Alamat tempat praktik" :value="$profile->place_address" class="sm:col-span-2" />
                <div class="sm:col-span-2 rounded-xl border border-garis bg-kertas p-4" x-data="{ mencari: false, pesan: '' }">
                    <p class="text-sm font-semibold text-arang">Titik basis layanan</p>
                    <p class="mt-1 text-xs leading-5 text-kabut">Dipakai untuk menghitung jarak pencarian. Lokasi hanya diminta setelah tombol ditekan.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <x-field name="service_lat" label="Lintang" type="number" step="any" :value="$profile->service_lat" min="-90" max="90" />
                        <x-field name="service_lng" label="Bujur" type="number" step="any" :value="$profile->service_lng" min="-180" max="180" />
                    </div>
                    <button type="button" @click="mencari = true; pesan = ''; navigator.geolocation.getCurrentPosition((pos) => { document.querySelector('[name=service_lat]').value = pos.coords.latitude; document.querySelector('[name=service_lng]').value = pos.coords.longitude; mencari = false; pesan = 'Lokasi berhasil diisi.' }, () => { mencari = false; pesan = 'Lokasi tidak dapat diambil. Isi koordinat secara manual.' }, { enableHighAccuracy: true, timeout: 10000 })" class="mt-3 rounded-xl border border-daun px-4 py-3 text-xs font-bold text-daun" :disabled="mencari"><span x-text="mencari ? 'Mengambil lokasi…' : 'Gunakan lokasi perangkat'"></span></button>
                    <p class="mt-2 text-xs text-kabut" role="status" aria-live="polite" x-text="pesan"></p>
                </div>
            </div>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Rekening penarikan</h2>
            <p class="mt-1 text-sm text-kabut">Data ini digunakan untuk pencairan saldo.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-field name="bank_name" label="Nama bank" :value="$profile->bank_name" />
                <x-field name="bank_account_number" label="Nomor rekening" :value="$profile->bank_account_number" inputmode="numeric" />
                <x-field name="bank_account_name" label="Nama pemilik rekening" :value="$profile->bank_account_name" class="sm:col-span-2" />
            </div>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Layanan & harga</h2>
            <div class="mt-5 space-y-3">
                @foreach ($services as $service)
                    @php $current = $selected->get($service->id); @endphp
                    <div class="grid gap-3 rounded-xl border border-garis p-4 sm:grid-cols-[1fr_9rem_8rem] sm:items-end">
                        <label class="flex items-center gap-3 text-sm font-semibold text-arang"><input type="checkbox" name="services[]" value="{{ $service->id }}" @checked(in_array($service->id, old('services', $selected->keys()->all()))) class="h-4 w-4 accent-daun"> {{ $service->name }}</label>
                        <x-field name="price[{{ $service->id }}]" label="Harga" type="number" :value="$current?->pivot->price" min="0" />
                        <x-field name="duration[{{ $service->id }}]" label="Durasi (menit)" type="number" :value="$current?->pivot->duration_min ?? 60" min="15" max="480" />
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-6">
            <h2 class="font-display text-xl font-bold text-arang">Jadwal layanan</h2>
            <p class="mt-1 text-sm text-kabut">Aktifkan hari kerja dan tentukan jam pelanggan dapat memesan.</p>
            <div class="mt-5 divide-y divide-garis rounded-xl border border-garis">
                @foreach ($days as $day => $label)
                    @php
                        $schedule = $savedSchedules->get($day);
                        $active = old("schedules.$day.active", $profile->schedule_configured ? (bool) $schedule : true);
                    @endphp
                    <div class="grid gap-3 p-4 sm:grid-cols-[8rem_1fr_1fr] sm:items-end">
                        <div>
                            <input type="hidden" name="schedules[{{ $day }}][day]" value="{{ $day }}">
                            <input type="hidden" name="schedules[{{ $day }}][active]" value="0">
                            <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-arang">
                                <input type="checkbox" name="schedules[{{ $day }}][active]" value="1" @checked($active) class="h-4 w-4 accent-daun">
                                {{ $label }}
                            </label>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-kabut">Mulai</label>
                            <input type="time" name="schedules[{{ $day }}][start]" value="{{ old("schedules.$day.start", $schedule?->start_time ? substr($schedule->start_time, 0, 5) : '08:00') }}" class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none focus:border-daun">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-kabut">Selesai</label>
                            <input type="time" name="schedules[{{ $day }}][end]" value="{{ old("schedules.$day.end", $schedule?->end_time ? substr($schedule->end_time, 0, 5) : '20:00') }}" class="w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm text-arang outline-none focus:border-daun">
                            @error("schedules.$day.end")<p class="mt-1 text-xs text-jahe">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <button class="w-full rounded-full bg-daun px-6 py-3.5 font-bold text-white hover:bg-daun-tua">Simpan perubahan</button>
    </form>
</div>
@endsection
