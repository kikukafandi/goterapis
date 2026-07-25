<footer class="border-t border-garis bg-white">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="max-w-xs">
                <p class="font-display text-lg font-semibold text-daun">GoTerapis</p>
                <p class="mt-2 text-sm text-kabut">
                    Temukan terapis pijat, bekam, kretek, dan refleksi di sekitarmu.
                    Terpercaya, transparan, dan tercatat.
                </p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-kabut">Layanan</p>
                <ul class="mt-3 space-y-2 text-sm text-arang/80">
                    <li><a class="hover:text-daun" href="/cari?kategori=pijat">Pijat</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=bekam">Bekam</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=kretek">Kretek</a></li>
                    <li><a class="hover:text-daun" href="/cari?kategori=refleksi">Refleksi</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-kabut">Terapis</p>
                <ul class="mt-3 space-y-2 text-sm text-arang/80">
                    <li><a class="hover:text-daun" href="/daftar-terapis">Gabung komunitas</a></li>
                    <li><a class="hover:text-daun" href="{{ route('artikel.index') }}">Info Sehat</a></li>
                    <li><a class="hover:text-daun" href="#verifikasi">Status verifikasi</a></li>
                    <li><a class="hover:text-daun" href="#cara-kerja">Cara kerja</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-kabut">Penting</p>
                <p class="mt-3 text-sm text-kabut">
                    GoTerapis bukan rumah sakit atau klinik dan tidak menggantikan tenaga medis.
                    Untuk keadaan darurat, hubungi tenaga kesehatan.
                </p>
            </div>
        </div>
        <p class="mt-10 text-xs text-kabut">© {{ date('Y') }} GoTerapis. Layanan kebugaran & terapi tradisional.</p>
    </div>
</footer>
