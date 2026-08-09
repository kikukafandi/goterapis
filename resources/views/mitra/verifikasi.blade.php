@extends('layouts.app')
@section('title', 'Status Verifikasi')

@php
    $documentLabels = [
        'ktp' => 'KTP',
        'rekening' => 'Buku rekening',
        'sertifikat_pelatihan' => 'Sertifikat pelatihan',
        'sertifikat_pengalaman' => 'Sertifikat pengalaman',
        'stpt' => 'STPT',
        'foto_tempat' => 'Foto tempat praktik',
    ];
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $statusStyles = [
        'pending' => 'bg-kunyit/10 text-kabut',
        'approved' => 'bg-daun-muda text-daun-tua',
        'rejected' => 'bg-jahe/10 text-jahe',
    ];
    $allApproved = $profile->documents->isNotEmpty() && $profile->documents->every(fn ($document) => $document->status === 'approved');
    $hasRejected = $profile->documents->contains('status', 'rejected');
@endphp

@section('content')
<div class="mx-auto grid max-w-6xl items-start gap-7 px-4 pb-28 pt-8 lg:grid-cols-[1fr_23rem] lg:pt-10">
    <div class="space-y-5">
        <section class="rounded-card p-7 text-white sm:p-9 {{ $hasRejected ? 'bg-jahe-terang' : 'bg-daun-terang' }}">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/20">
                <x-icon name="shield" class="h-7 w-7" />
            </span>
            <h1 class="mt-5 max-w-xl font-display text-3xl font-bold sm:text-4xl">
                {{ $hasRejected ? 'Ada dokumen yang perlu diperbaiki' : ($allApproved ? 'Dokumenmu sudah disetujui' : 'Pendaftaranmu sedang kami periksa') }}
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-white/85">
                {{ $hasRejected ? 'Periksa catatan tim kami pada dokumen yang ditolak, lalu siapkan dokumen pengganti yang lebih jelas.' : ($allApproved ? 'Seluruh dokumen sudah ditinjau. Status profilmu saat ini '.$profile->statusLabel().'.' : 'Pemeriksaan dilakukan manual dan biasanya selesai dalam 1–2 hari kerja. Kamu akan mendapat kabar setelah proses selesai.') }}
            </p>
        </section>

        <section class="rounded-card border border-garis bg-white p-5 sm:p-7">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-display text-xl font-bold text-arang">Dokumenmu</h2>
                <span class="text-xs font-semibold text-kabut">{{ $profile->documents->count() }} dokumen diunggah</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($profile->documents as $document)
                    <article class="rounded-2xl border border-garis p-4 sm:p-5">
                        <div class="flex items-center gap-4">
                            <span class="grid h-14 w-16 shrink-0 place-items-center rounded-xl bg-kertas text-kabut">
                                <x-icon name="clipboard" class="h-6 w-6" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-bold text-arang">{{ $documentLabels[$document->type] ?? $document->type }}</h3>
                                <p class="mt-1 text-xs text-kabut">Diunggah {{ $document->created_at->translatedFormat('j M Y') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-2 text-[11px] font-bold {{ $statusStyles[$document->status] ?? 'bg-kertas text-kabut' }}">
                                {{ $statusLabels[$document->status] ?? $document->status }}
                            </span>
                        </div>
                        @if ($document->status === 'rejected')
                            @if ($document->note)
                                <p class="mt-4 rounded-xl border border-jahe/20 bg-jahe/10 px-4 py-3 text-xs leading-5 text-jahe">{{ $document->note }}</p>
                            @endif
                            <form method="post" action="{{ route('mitra.dokumen.replace', $document) }}" enctype="multipart/form-data" class="mt-4 rounded-xl border border-garis bg-kertas p-4">
                                @csrf @method('put')
                                <label for="document-{{ $document->id }}" class="block text-xs font-bold text-arang">Unggah dokumen pengganti</label>
                                <input id="document-{{ $document->id }}" name="document" type="file" required accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 w-full rounded-xl border border-garis bg-white px-3 py-2.5 text-xs">
                                <p class="mt-2 text-xs text-kabut">JPG, PNG, WebP, atau PDF. Maksimal 4 MB.</p>
                                <button class="mt-3 rounded-xl bg-daun px-4 py-3 text-xs font-bold text-white">Kirim pengganti</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="rounded-2xl bg-kertas px-5 py-8 text-center text-sm text-kabut">Belum ada dokumen yang tersimpan.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="rounded-card border border-garis bg-white p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-daun">Status profil</p>
            <h2 class="mt-2 font-display text-xl font-bold text-arang">{{ $profile->statusLabel() }}</h2>
            <p class="mt-3 text-sm leading-6 text-kabut">Sambil menunggu, lengkapi jadwal, layanan, dan informasi profil agar siap menerima pesanan.</p>
            <a href="{{ route('mitra.profil.edit') }}" class="mt-5 flex w-full items-center justify-center rounded-xl border border-garis px-4 py-3 text-sm font-bold text-arang transition-colors hover:border-daun hover:text-daun">Lengkapi profil</a>
        </section>
        <section class="rounded-card bg-arang p-6 text-white">
            <p class="text-sm font-bold text-daun-muda">Aplikasi mitra</p>
            <p class="mt-3 text-xs leading-6 text-white/60">Terima pesanan, bagikan lokasi saat perjalanan, dan kelola saldo langsung dari ponselmu.</p>
        </section>
    </aside>
</div>
@endsection
