<footer class="border-t border-garis bg-white">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="max-w-xs">
                <x-logo variant="full" class="h-14" />
                <p class="mt-3 text-sm leading-relaxed text-kabut-muda">
                    Bandingkan layanan, harga, dan ulasan terapis di sekitarmu.
                    Pilih jadwal yang sesuai, lalu pesan melalui GoTerapis.
                </p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[.06em] text-kabut-samar">Layanan</p>
                <ul class="mt-3 space-y-2 text-sm text-kabut">
                    <li><a class="hover:text-daun" href="/cari?kategori=pijat">Pijat</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=bekam">Bekam</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=kretek">Kretek</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=refleksi">Refleksi</a></li>
                </ul>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[.06em] text-kabut-samar">Terapis</p>
                <ul class="mt-3 space-y-2 text-sm text-kabut">
                    <li><a class="hover:text-daun" href="/daftar-terapis">Gabung komunitas</a></li>
                    <li><a class="hover:text-daun" href="{{ route('artikel.index') }}">Info Sehat</a></li>
                    <li><a class="hover:text-daun" href="{{ route('products.index') }}">Toko</a></li>
                    <li><a class="hover:text-daun" href="#verifikasi">Status verifikasi</a></li>
                    <li><a class="hover:text-daun" href="#cara-kerja">Cara kerja</a></li>
                </ul>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[.06em] text-kabut-samar">Hukum & bantuan</p>
                <ul class="mt-3 space-y-2 text-sm text-kabut">
                    @foreach (config('legal.documents') as $slug => $document)
                        <li><a class="hover:text-daun" href="{{ route('legal.show', $slug) }}">{{ $document['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="mt-10 border-t border-garis pt-6 text-xs text-kabut-samar">© {{ date('Y') }} GoTerapis. Layanan kebugaran & terapi tradisional.</p>
    </div>
</footer>
