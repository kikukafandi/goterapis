@extends('layouts.admin')
@section('title', 'Laporan')
@section('heading', 'Laporan perilaku')
@section('subheading', 'Tinjau laporan pelecehan secara rahasia dan hati-hati')
@section('content')
@php($labels = ['open' => 'Terbuka', 'reviewing' => 'Ditinjau', 'resolved' => 'Selesai', 'dismissed' => 'Ditutup'])
<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach ([null => 'Semua', 'open' => 'Terbuka', 'reviewing' => 'Ditinjau', 'resolved' => 'Selesai', 'dismissed' => 'Ditutup'] as $value => $label)
            <a href="{{ route('admin.reports.index', array_filter(['status' => $value])) }}" class="rounded-xl border px-4 py-2.5 text-xs font-bold {{ $status === $value ? 'border-arang bg-arang text-white' : 'border-garis bg-white text-kabut' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="kartu overflow-hidden">
        @forelse ($reports as $report)
            <a href="{{ route('admin.reports.show', $report) }}" class="grid gap-3 border-b border-garis p-5 last:border-0 hover:bg-kertas sm:grid-cols-[1.4fr_1.4fr_1fr_auto] sm:items-center">
                <span><strong class="block text-sm text-arang">{{ $report->reporter->name }}</strong><small class="text-kabut">melaporkan {{ $report->reportedUser?->name ?? 'pengguna' }}</small></span>
                <span class="text-sm font-semibold text-arang">{{ str($report->reason)->replace('_', ' ')->title() }}</span>
                <span class="text-xs text-kabut">{{ $report->created_at->translatedFormat('d M Y · H:i') }}</span>
                <span class="rounded-full bg-kunyit-muda px-3 py-1.5 text-xs font-bold text-kunyit-tua">{{ $labels[$report->status] }}</span>
            </a>
        @empty
            <div class="p-14 text-center"><h2 class="font-display font-bold text-arang">Belum ada laporan</h2><p class="mt-1 text-sm text-kabut">Laporan pengguna akan tampil di sini.</p></div>
        @endforelse
        @if ($reports->hasPages())<div class="border-t border-garis p-4">{{ $reports->links() }}</div>@endif
    </div>
</div>
@endsection
