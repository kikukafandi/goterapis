@extends('layouts.admin')
@section('title', 'Detail Terapis')
@section('heading', 'Detail Terapis')

@php
    $docLabels = [
        'ktp' => 'KTP', 'sertifikat_pelatihan' => 'Sertifikat Pelatihan',
        'sertifikat_pengalaman' => 'Sertifikat Pengalaman', 'stpt' => 'STPT',
        'foto_tempat' => 'Foto Tempat Praktik', 'rekening' => 'Rekening',
    ];
    $statusBadge = ['pending' => 'bg-kunyit-muda text-kunyit', 'approved' => 'bg-daun-muda text-daun-tua', 'rejected' => 'bg-jahe/15 text-jahe'];
@endphp

@section('content')
<a href="{{ route('admin.therapists') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-daun hover:underline">
    ← Kembali
</a>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Profil + status --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-garis bg-white p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-daun-muda text-xl font-semibold text-daun">
                    {{ mb_substr($therapist->user->name, 0, 1) }}
                </span>
                <div>
                    <p class="font-semibold text-arang">{{ $therapist->user->name }}</p>
                    <p class="text-sm text-kabut">{{ $therapist->user->phone ?? '—' }}</p>
                </div>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-kabut">Email</dt><dd class="font-medium text-arang">{{ $therapist->user->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-kabut">Wilayah</dt><dd class="font-medium text-arang">{{ $therapist->city ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-kabut">Model</dt><dd class="font-medium text-arang">{{ collect(['serves_call' => 'Panggilan', 'serves_place' => 'Tempat'])->filter(fn ($l, $k) => $therapist->$k)->implode(', ') ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-kabut">Pengalaman</dt><dd class="font-medium text-arang">{{ $therapist->experience_years }} th</dd></div>
            </dl>
            @if ($therapist->bio)
                <p class="mt-4 border-t border-garis pt-3 text-sm text-kabut">{{ $therapist->bio }}</p>
            @endif
        </div>

        {{-- Ubah status verifikasi --}}
        <form method="post" action="{{ route('admin.therapist.status', $therapist) }}" class="rounded-2xl border border-garis bg-white p-5">
            @csrf @method('patch')
            <p class="font-semibold text-arang">Status verifikasi</p>
            <select name="verification_status" class="mt-3 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-sm outline-none focus:border-daun">
                @foreach (\App\Models\TherapistProfile::STATUS_LABELS as $k => $v)
                    <option value="{{ $k }}" @selected($therapist->verification_status === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <label class="mt-3 flex items-center gap-2 text-sm text-kabut">
                <input type="checkbox" name="is_featured" value="1" @checked($therapist->is_featured)
                       class="rounded border-garis text-daun focus:ring-daun">
                Tampilkan sebagai "Dipromosikan"
            </label>
            <button class="mt-4 w-full rounded-full bg-daun px-4 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">Simpan status</button>
        </form>

        {{-- Layanan --}}
        @if ($therapist->services->isNotEmpty())
            <div class="rounded-2xl border border-garis bg-white p-5">
                <p class="font-semibold text-arang">Layanan & harga</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($therapist->services as $s)
                        <li class="flex justify-between">
                            <span class="text-arang">{{ $s->name }} <span class="text-kabut">· {{ $s->pivot->duration_min }} mnt</span></span>
                            <span class="font-semibold text-arang">Rp{{ number_format($s->pivot->price, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Dokumen --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-garis bg-white p-5">
            <p class="font-semibold text-arang">Dokumen ({{ $therapist->documents->count() }})</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @forelse ($therapist->documents as $doc)
                    <div class="overflow-hidden rounded-xl border border-garis">
                        <a href="{{ Storage::url($doc->path) }}" target="_blank" class="block">
                            <img src="{{ Storage::url($doc->path) }}" alt="{{ $docLabels[$doc->type] ?? $doc->type }}"
                                 class="h-40 w-full bg-kertas object-cover">
                        </a>
                        <div class="p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-arang">{{ $docLabels[$doc->type] ?? $doc->type }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge[$doc->status] }}">{{ ucfirst($doc->status) }}</span>
                            </div>
                            @if ($doc->note)<p class="mt-1 text-xs text-jahe">{{ $doc->note }}</p>@endif
                            <div class="mt-3 flex gap-2">
                                <form method="post" action="{{ route('admin.document.review', $doc) }}" class="flex-1">
                                    @csrf @method('patch')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="w-full rounded-lg bg-daun px-3 py-1.5 text-xs font-semibold text-white hover:bg-daun-tua">Setujui</button>
                                </form>
                                <form method="post" action="{{ route('admin.document.review', $doc) }}" class="flex-1"
                                      x-data @submit.prevent="$refs.note.value = prompt('Alasan penolakan (opsional):') ?? ''; $el.submit()">
                                    @csrf @method('patch')
                                    <input type="hidden" name="status" value="rejected">
                                    <input type="hidden" name="note" x-ref="note">
                                    <button class="w-full rounded-lg border border-jahe/40 px-3 py-1.5 text-xs font-semibold text-jahe hover:bg-jahe/10">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-kabut">Belum ada dokumen diunggah.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
