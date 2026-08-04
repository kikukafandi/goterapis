@extends('layouts.admin')
@section('title', $article->exists ? 'Ubah artikel' : 'Tulis artikel')
@section('heading', $article->exists ? 'Ubah artikel' : 'Tulis artikel')

@section('content')
@if ($errors->any())
    <div role="alert" class="mb-5 rounded-xl border border-jahe/30 bg-jahe/10 px-4 py-3 text-sm text-jahe">
        <p class="mb-2 font-semibold">Periksa kembali isian berikut.</p>
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="post" enctype="multipart/form-data"
      action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
      x-data="artikelEditor({
          title: @js(old('title', $article->title)),
          metaTitle: @js(old('meta_title', $article->meta_title)),
          excerpt: @js(old('excerpt', $article->excerpt)),
      })"
      class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start lg:gap-8">
    @csrf
    @if ($article->exists) @method('PUT') @endif

    {{-- ===== KIRI: konten (lebar) ===== --}}
    <div class="min-w-0 space-y-4">
        <input name="title" x-model="title" required autofocus aria-label="Judul artikel" placeholder="Judul artikel"
               class="w-full bg-transparent font-display text-2xl font-bold text-arang outline-none placeholder:text-kabut/40 sm:text-3xl">

        {{-- Editor WYSIWYG (CKEditor 5) menggantikan textarea ini --}}
        <textarea id="body-editor" name="body" data-upload-url="{{ route('admin.articles.upload') }}">{!! old('body', $article->body) !!}</textarea>

        <div class="flex items-center justify-between">
            <p class="text-xs text-kabut">Gambar yang disisipkan otomatis tersimpan di server.</p>
            <div id="editor-wordcount" class="text-xs text-kabut"></div>
        </div>
    </div>

    {{-- ===== KANAN: SEO, gambar & atribut ===== --}}
    <aside class="space-y-4 lg:sticky lg:top-20">
        {{-- Aksi --}}
        <div class="rounded-card border border-garis bg-white p-4">
            <button class="w-full rounded-full bg-daun px-5 py-2.5 text-sm font-semibold text-white hover:bg-daun-tua">
                {{ $article->exists ? 'Simpan perubahan' : 'Simpan artikel' }}
            </button>
            <a href="{{ route('admin.articles.index') }}"
               class="mt-2 block text-center text-sm font-semibold text-kabut hover:text-arang">Batal</a>
        </div>

        {{-- SEO --}}
        <div class="rounded-card border border-garis bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-kabut">SEO</p>

            <label class="mt-3 block text-sm font-semibold text-arang">Judul SEO</label>
            <input name="meta_title" x-model="metaTitle" maxlength="60" x-bind:placeholder="title || 'Judul di hasil pencarian'"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-white px-3 py-2 text-sm outline-none focus:border-daun">
            <div class="mt-1 flex justify-between text-xs text-kabut">
                <span>Kosongkan untuk memakai judul artikel.</span>
                <span :class="metaTitle.length > 60 && 'text-jahe'" x-text="metaTitle.length + '/60'"></span>
            </div>

            <label class="mt-4 block text-sm font-semibold text-arang">Ringkasan</label>
            <textarea name="excerpt" x-model="excerpt" rows="3" maxlength="255"
                      placeholder="Deskripsi singkat untuk pratinjau & mesin pencari."
                      class="mt-1.5 w-full resize-y rounded-xl border border-garis bg-white px-3 py-2 text-sm outline-none focus:border-daun"></textarea>
            <div class="mt-1 text-right text-xs text-kabut" x-text="excerpt.length + '/255'"></div>

            <p class="mt-3 truncate text-xs text-kabut">Tautan: <span class="text-daun" x-text="'/artikel/' + (slug || '…')"></span></p>
        </div>

        {{-- Gambar sampul (pratinjau + crop 3:2) --}}
        <div class="rounded-card border border-garis bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-kabut">Gambar sampul</p>
            <img id="cover-preview" src="{{ $article->coverUrl() }}" alt="Pratinjau sampul"
                 class="mt-3 aspect-[3/2] w-full rounded-xl object-cover {{ $article->coverUrl() ? '' : 'hidden' }}">
            <label class="mt-3 flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-garis px-3 py-2.5 text-sm hover:border-daun">
                <x-icon name="upload" class="h-5 w-5 shrink-0 text-daun" />
                <span class="text-kabut">Pilih & sesuaikan gambar…</span>
                <input id="cover-input" type="file" name="cover" accept="image/*" class="sr-only">
            </label>
            <p class="mt-2 text-xs text-kabut">Setelah memilih, kamu bisa memotong gambar ke rasio 3:2.</p>
        </div>

        {{-- Terbit --}}
        <div class="rounded-card border border-garis bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-kabut">Terbit</p>
            <label class="mt-3 block text-sm font-semibold text-arang">Tanggal terbit</label>
            <input type="datetime-local" name="published_at"
                   value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-white px-3 py-2 text-sm outline-none focus:border-daun">
            <p class="mt-1.5 text-xs text-kabut">Kosongkan untuk menyimpan sebagai draf.</p>
        </div>
    </aside>
</form>

{{-- Dialog crop gambar sampul --}}
<dialog id="crop-dialog" aria-labelledby="crop-title" class="w-[92vw] max-w-lg rounded-card p-0 backdrop:bg-arang/50">
    <div class="border-b border-garis px-5 py-3">
        <h3 id="crop-title" class="font-display font-bold text-arang">Sesuaikan gambar sampul</h3>
        <p class="text-xs text-kabut">Geser & perbesar untuk memilih area (rasio 3:2).</p>
    </div>
    <div class="bg-kertas p-4">
        <div class="max-h-[60vh] overflow-hidden">
            <img id="crop-image" alt="" class="block max-w-full">
        </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-garis px-5 py-3">
        <button type="button" id="crop-cancel" class="rounded-full border border-garis px-4 py-2 text-sm font-semibold text-arang hover:bg-kertas">Batal</button>
        <button type="button" id="crop-apply" class="rounded-full bg-daun px-4 py-2 text-sm font-semibold text-white hover:bg-daun-tua">Terapkan</button>
    </div>
</dialog>

<style>
    /* Selaraskan tinggi & sudut editor CKEditor dengan gaya form */
    .ck-editor__editable { min-height: 26rem; }
    .ck.ck-editor__main > .ck-editor__editable { border-radius: 0 0 1rem 1rem; }
    .ck.ck-toolbar { border-radius: 1rem 1rem 0 0; }
</style>

<script>
    function artikelEditor(initial) {
        return {
            title: initial.title ?? '',
            metaTitle: initial.metaTitle ?? '',
            excerpt: initial.excerpt ?? '',
            get slug() {
                return this.title.toLowerCase().trim()
                    .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
            },
        };
    }
</script>

@vite('resources/js/artikel-editor.js')
@endsection
