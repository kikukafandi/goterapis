@extends('layouts.admin')
@section('title', 'Detail laporan')
@section('heading', 'Detail laporan')
@section('subheading', 'Bukti tersimpan saat laporan dikirim')
@section('content')
@php($labels = ['open' => 'Terbuka', 'reviewing' => 'Ditinjau', 'resolved' => 'Selesai', 'dismissed' => 'Ditutup'])
<div class="grid gap-5 lg:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <section class="kartu p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><span class="text-xs text-kabut">Pelapor</span><p class="font-bold text-arang">{{ $report->reporter->name }}</p></div>
                <div><span class="text-xs text-kabut">Terlapor</span><p class="font-bold text-arang">{{ $report->reportedUser?->name ?? 'Tidak tersedia' }}</p></div>
                <div><span class="text-xs text-kabut">Jenis</span><p class="font-bold text-arang">{{ str($report->reason)->replace('_', ' ')->title() }}</p></div>
                <div><span class="text-xs text-kabut">Dikirim</span><p class="font-bold text-arang">{{ $report->created_at->translatedFormat('d F Y · H:i') }}</p></div>
            </div>
            <div class="mt-5 border-t border-garis pt-5"><span class="text-xs text-kabut">Keterangan pelapor</span><p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-arang">{{ $report->detail }}</p></div>
        </section>
        <section class="kartu p-6">
            <h2 class="font-display text-lg font-bold text-arang">Bukti percakapan</h2>
            <div class="mt-4 max-h-[500px] space-y-3 overflow-y-auto rounded-xl bg-kertas p-4">
                @forelse (data_get($report->evidence, 'chat', []) as $message)
                    <div class="rounded-xl border border-garis bg-white p-3"><p class="text-xs font-bold text-kabut">Pengguna #{{ $message['sender_id'] }}</p><p class="mt-1 whitespace-pre-wrap text-sm text-arang">{{ $message['body'] }}</p></div>
                @empty
                    <p class="text-sm text-kabut">Tidak ada pesan saat bukti diamankan.</p>
                @endforelse
            </div>
        </section>
    </div>
    <aside class="kartu self-start p-6 lg:sticky lg:top-24">
        <h2 class="font-display text-lg font-bold text-arang">Peninjauan</h2>
        <form method="post" action="{{ route('admin.reports.update', $report) }}" class="mt-4 space-y-4">
            @csrf @method('PATCH')
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-arang">Status</span><select name="status" class="isian">@foreach ($labels as $value => $label)<option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>@endforeach</select></label>
            @if ($report->reportable instanceof \App\Models\Order && $report->reportable->status === 'disputed')
                <div class="rounded-card border border-garis bg-kertas p-4 text-xs leading-relaxed text-kabut">Dana pesanan sedang ditahan. Saat menutup laporan, pilih pencairan ke terapis atau refund penuh ke pelanggan. Keputusan yang tersimpan tidak dapat dijalankan ulang.</div>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold text-arang">Hasil akhir</span><select name="resolution" class="isian"><option value="release">Selesaikan pesanan dan cairkan ke terapis</option><option value="refund">Refund penuh ke pelanggan</option></select></label>
            @endif
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-arang">Catatan admin</span><textarea name="admin_note" maxlength="5000" rows="7" class="isian resize-y">{{ $report->admin_note }}</textarea></label>
            <button class="btn-utama w-full">Simpan peninjauan</button>
        </form>
        @if ($report->reviewer)<p class="mt-4 text-xs leading-relaxed text-kabut">Terakhir ditinjau {{ $report->reviewer->name }} · {{ $report->reviewed_at?->translatedFormat('d M Y H:i') }}</p>@endif
    </aside>
</div>
@endsection
