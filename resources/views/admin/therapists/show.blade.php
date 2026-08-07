@extends('layouts.admin')
@section('title', 'Detail Terapis')
@section('heading', 'Detail terapis')
@section('subheading', 'Tinjau dokumen satu per satu, lalu tetapkan status verifikasi')

@php
    $docLabels = [
        'ktp' => 'KTP', 'sertifikat_pelatihan' => 'Sertifikat Pelatihan',
        'sertifikat_pengalaman' => 'Sertifikat Pengalaman', 'stpt' => 'STPT',
        'foto_tempat' => 'Foto Tempat Praktik', 'rekening' => 'Rekening',
    ];
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $badgeStyle = [
        'pending' => 'bg-kunyit-muda text-kunyit-tua',
        'approved' => 'bg-daun-muda text-daun-tua',
        'rejected' => 'bg-jahe-muda text-jahe',
    ];
    $borderStyle = [
        'pending' => 'border-kunyit-garis',
        'approved' => 'border-garis-muda',
        'rejected' => 'border-jahe-garis',
    ];
    $menunggu = $therapist->documents->where('status', 'pending')->count();
    $model = collect(['serves_call' => 'Panggilan', 'serves_place' => 'Di tempat'])
        ->filter(fn ($l, $k) => $therapist->$k)->implode(' · ') ?: '—';
@endphp

@section('content')
<div class="grid items-start gap-5 xl:grid-cols-[1fr_340px]">

    <div class="flex flex-col gap-5">

        {{-- Identitas --}}
        <div class="kartu flex flex-wrap items-center gap-4 p-6 sm:gap-5">
            @if ($therapist->user->avatarUrl())
                <img src="{{ $therapist->user->avatarUrl() }}" alt="" class="h-[76px] w-[76px] shrink-0 rounded-[22px] object-cover">
            @else
                <span class="grid h-[76px] w-[76px] shrink-0 place-items-center rounded-[22px] bg-daun-muda text-2xl font-extrabold text-daun">{{ mb_substr($therapist->user->name, 0, 1) }}</span>
            @endif
            <div class="flex min-w-0 flex-1 flex-col gap-2">
                <h2 class="font-display truncate text-[22px] font-extrabold text-arang">{{ $therapist->user->name }}</h2>
                <p class="truncate text-xs font-medium text-kabut-muda">{{ $therapist->user->phone ?? 'Nomor belum diisi' }} · {{ $therapist->user->email }}</p>
                <p class="truncate text-xs font-medium text-kabut-samar">{{ $therapist->city ?? 'Kota belum diisi' }} · {{ $therapist->experience_years }} th pengalaman · bergabung {{ $therapist->created_at->translatedFormat('j M Y') }}</p>
            </div>
            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                <span class="rounded-full bg-daun-muda px-3.5 py-2 text-[11px] font-bold text-daun-tua">{{ $therapist->statusLabel() }}</span>
                <span class="text-[11px] font-medium text-kabut-samar">{{ number_format($therapist->rating_avg, 1, ',', '.') }} ★ · {{ $therapist->completed_count }} sesi</span>
            </div>
        </div>

        {{-- Dokumen --}}
        <div class="kartu flex flex-col gap-4 p-6">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="font-display text-[17px] font-extrabold text-arang">Dokumen</h2>
                <span class="shrink-0 text-xs font-medium text-kabut-samar">{{ $menunggu === 0 ? 'Semua dokumen sudah ditinjau' : $menunggu.' dokumen menunggu tinjauan' }}</span>
            </div>

            @forelse ($therapist->documents as $doc)
                <div class="flex flex-col gap-4 rounded-2xl border {{ $borderStyle[$doc->status] ?? 'border-garis-muda' }} p-4 sm:p-[18px]">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="grid h-[54px] w-[72px] shrink-0 place-items-center rounded-xl bg-garis-muda text-[10px] font-semibold text-kabut-samar">SCAN</span>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <span class="truncate text-sm font-bold text-arang">{{ $docLabels[$doc->type] ?? $doc->type }}</span>
                            <span class="truncate text-[11px] font-medium text-kabut-samar">Diunggah {{ $doc->created_at->translatedFormat('j M Y') }}</span>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-2 text-[10px] font-bold {{ $badgeStyle[$doc->status] ?? 'bg-kertas text-kabut' }}">{{ $statusLabels[$doc->status] ?? $doc->status }}</span>
                        <a href="{{ route('admin.document.download', $doc) }}"
                           class="shrink-0 rounded-[10px] border border-garis bg-white px-3.5 py-2.5 text-[11px] font-semibold text-kabut hover:bg-kertas">Unduh</a>
                    </div>

                    @if ($doc->status === 'pending')
                        <form method="post" action="{{ route('admin.document.review', $doc) }}"
                              class="flex flex-wrap items-center gap-2.5 border-t border-garis-muda pt-4">
                            @csrf @method('patch')
                            <label class="min-w-0 flex-1">
                                <span class="sr-only">Catatan untuk terapis</span>
                                <input name="note" maxlength="255" placeholder="Catatan untuk terapis (opsional, maks 255)" class="isian text-xs">
                            </label>
                            <button name="status" value="rejected"
                                    class="shrink-0 rounded-xl border-[1.5px] border-jahe-garis bg-white px-4 py-3 text-xs font-bold text-jahe hover:bg-jahe-muda">Tolak</button>
                            <button name="status" value="approved"
                                    class="shrink-0 rounded-xl bg-daun px-5 py-3 text-xs font-bold text-white hover:bg-daun-tua">Setujui</button>
                        </form>
                    @elseif ($doc->status === 'rejected' && $doc->note)
                        <div class="flex gap-2.5 rounded-xl border border-jahe-garis bg-jahe-muda px-3.5 py-3">
                            <span class="mt-0.5 h-3 w-3 shrink-0 rounded-full bg-jahe-terang"></span>
                            <span class="text-xs font-medium leading-relaxed text-jahe">{{ $doc->note }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center gap-3 py-12 text-center">
                    <span class="grid h-[76px] w-[76px] place-items-center rounded-full bg-garis-muda text-kabut-samar"><x-icon name="clipboard" class="h-7 w-7" /></span>
                    <p class="font-display text-base font-extrabold text-arang">Belum ada dokumen</p>
                    <p class="max-w-xs text-xs leading-relaxed text-kabut-muda">Dokumen akan tampil di sini setelah terapis mengunggahnya.</p>
                </div>
            @endforelse
        </div>

        {{-- Layanan terdaftar --}}
        @if ($therapist->services->isNotEmpty())
            <div class="kartu flex flex-col gap-4 p-6">
                <h2 class="font-display text-[17px] font-extrabold text-arang">Layanan terdaftar</h2>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    @foreach ($therapist->services as $s)
                        <div class="flex items-center gap-3 rounded-2xl border border-garis-muda px-4 py-3.5">
                            <span class="flex min-w-0 flex-1 flex-col gap-1">
                                <span class="truncate text-[13px] font-bold text-arang">{{ $s->name }}</span>
                                <span class="text-[11px] font-medium text-kabut-samar">{{ $s->pivot->duration_min }} menit</span>
                            </span>
                            <span class="font-display shrink-0 text-sm font-extrabold text-arang">Rp{{ number_format($s->pivot->price, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Panel kanan --}}
    <div class="flex flex-col gap-5 xl:sticky xl:top-28">
        <form method="post" action="{{ route('admin.therapist.status', $therapist) }}" class="kartu flex flex-col gap-4 p-5 sm:p-6">
            @csrf @method('patch')
            <div class="flex flex-col gap-1.5">
                <h2 class="font-display text-base font-extrabold text-arang">Status verifikasi</h2>
                <p class="text-[11px] font-medium leading-relaxed text-kabut-samar">Menentukan badge yang tampil di profil publik.</p>
            </div>

            <div class="flex flex-col gap-2">
                @foreach (\App\Models\TherapistProfile::STATUS_LABELS as $k => $v)
                    @php $terpilih = $therapist->verification_status === $k; @endphp
                    <label class="flex cursor-pointer items-center gap-3 rounded-[13px] border-[1.5px] px-3.5 py-3 {{ $terpilih ? 'border-daun-terang' : 'border-garis-muda hover:border-garis' }}">
                        <input type="radio" name="verification_status" value="{{ $k }}" @checked($terpilih)
                               class="h-[18px] w-[18px] shrink-0 border-2 border-garis text-daun-terang focus:ring-daun-terang">
                        <span class="text-xs font-semibold text-arang">{{ $v }}</span>
                    </label>
                @endforeach
            </div>

            <label class="flex cursor-pointer items-center gap-3 rounded-[13px] border-[1.5px] p-3.5 {{ $therapist->is_featured ? 'border-daun-terang bg-daun-muda/40' : 'border-garis-muda' }}">
                <input type="checkbox" name="is_featured" value="1" @checked($therapist->is_featured)
                       class="h-[18px] w-[18px] shrink-0 rounded-[5px] border-2 border-garis text-daun-terang focus:ring-daun-terang">
                <span class="flex flex-col gap-1">
                    <span class="text-xs font-bold text-arang">Tampilkan di Terapis pilihan</span>
                    <span class="text-[10px] font-medium text-kabut-samar">Muncul di beranda pasien</span>
                </span>
            </label>

            <button class="rounded-[13px] bg-daun py-4 text-[13px] font-bold text-white hover:bg-daun-tua">Simpan status</button>
        </form>

        <div class="kartu flex flex-col gap-3.5 p-5 sm:p-6">
            <h2 class="font-display text-base font-extrabold text-arang">Ringkasan</h2>
            @foreach ([
                'Sesi selesai' => $therapist->completed_count,
                'Ulasan' => $therapist->reviews_count,
                'Biaya transport' => 'Rp'.number_format($therapist->transport_fee, 0, ',', '.'),
                'Model layanan' => $model,
            ] as $k => $v)
                <div class="flex justify-between gap-3.5">
                    <span class="text-xs font-medium text-kabut-muda">{{ $k }}</span>
                    <span class="text-xs font-bold text-arang">{{ $v }}</span>
                </div>
            @endforeach
        </div>

        @if ($therapist->bio)
            <div class="kartu flex flex-col gap-2.5 p-5 sm:p-6">
                <h2 class="font-display text-base font-extrabold text-arang">Bio</h2>
                <p class="text-xs leading-relaxed text-kabut">{{ $therapist->bio }}</p>
            </div>
        @endif

        <a href="{{ route('admin.therapists') }}" class="rounded-[13px] border border-garis bg-white py-4 text-center text-xs font-semibold text-kabut hover:bg-kertas">← Kembali ke daftar terapis</a>
    </div>
</div>
@endsection
