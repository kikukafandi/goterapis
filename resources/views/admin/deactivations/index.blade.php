@extends('layouts.admin')
@section('title', 'Penonaktifan')
@section('heading', 'Permintaan penonaktifan')
@section('subheading', 'Tinjau antrean dan riwayat permintaan mitra')
@section('content')
@php($labels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])
<div class="space-y-4">
@forelse ($requests as $item)
<div class="kartu p-5">
    <div class="flex justify-between gap-4"><div><strong class="text-sm text-arang">{{ $item->user->name }}</strong><p class="text-xs text-kabut">{{ $item->user->email }} · {{ $item->created_at->translatedFormat('d M Y H:i') }}</p></div><span class="text-xs font-bold text-kabut">{{ $labels[$item->status] }}</span></div>
    <p class="mt-3 text-sm text-arang">{{ $item->reason ?: 'Tanpa alasan.' }}</p>
    @if ($item->status === 'pending')
    <form method="post" action="{{ route('admin.deactivations.update', $item) }}" class="mt-4 flex flex-wrap gap-2">@csrf @method('patch')
        <input name="admin_note" required maxlength="2000" placeholder="Catatan admin wajib" class="min-w-60 flex-1 rounded-xl border border-garis bg-kertas-isian px-3 py-2 text-sm">
        <button name="status" value="rejected" class="btn-garis text-xs">Tolak</button><button name="status" value="approved" class="btn-utama text-xs">Setujui</button>
    </form>
    @else <p class="mt-3 text-xs text-kabut">Catatan {{ $item->reviewer?->name }}: {{ $item->admin_note }}</p> @endif
</div>
@empty <div class="kartu p-12 text-center text-sm text-kabut">Belum ada permintaan penonaktifan.</div> @endforelse
{{ $requests->links() }}
</div>
@endsection
