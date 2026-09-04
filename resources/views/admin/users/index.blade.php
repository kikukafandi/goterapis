@extends('layouts.admin')
@section('title', 'Pengguna')
@section('heading', 'Pengguna')
@section('subheading', 'Cari dan kelola akses pengguna')
@section('content')
<form class="mb-4 flex gap-2" method="get"><input name="q" value="{{ $search }}" placeholder="Cari nama atau email" class="w-full rounded-xl border border-garis bg-white px-4 py-3 text-sm"><button class="btn-utama">Cari</button></form>
<div class="kartu overflow-hidden">
@forelse ($users as $user)
<div class="grid gap-3 border-b border-garis p-5 last:border-0 md:grid-cols-[1fr_auto] md:items-center">
    <div><strong class="text-sm text-arang">{{ $user->name }}</strong><p class="text-xs text-kabut">{{ $user->email }} · {{ $user->role }}</p>@if($user->activeBan)<p class="mt-1 text-xs font-semibold text-jahe">Diblokir: {{ $user->activeBan->reason }}{{ $user->activeBan->expires_at ? ' hingga '.$user->activeBan->expires_at->translatedFormat('d M Y H:i') : ' permanen' }}</p>@endif</div>
    @if ($user->role !== 'admin')
        @if ($user->activeBan)
            <form method="post" action="{{ route('admin.users.unban', $user) }}">@csrf @method('patch')<button class="btn-garis text-xs">Buka blokir</button></form>
        @else
            <form method="post" action="{{ route('admin.users.ban', $user) }}" class="flex flex-wrap gap-2">@csrf
                <select name="duration" class="rounded-xl border border-garis bg-white px-3 text-xs"><option value="1">1 hari</option><option value="7">7 hari</option><option value="30">30 hari</option><option value="permanent">Permanen</option></select>
                <input name="reason" required maxlength="2000" placeholder="Alasan wajib" class="rounded-xl border border-garis bg-white px-3 py-2 text-xs">
                <button class="rounded-xl bg-jahe px-4 py-2 text-xs font-bold text-white">Blokir</button>
            </form>
        @endif
    @endif
</div>
@empty <div class="p-12 text-center text-sm text-kabut">Pengguna tidak ditemukan.</div> @endforelse
@if($users->hasPages())<div class="border-t border-garis p-4">{{ $users->links() }}</div>@endif
</div>
@endsection
