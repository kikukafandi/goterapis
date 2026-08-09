<footer class="mt-14 bg-malam px-4 pb-9 pt-12 text-white">
    <div class="mx-auto grid max-w-6xl gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-[13px] bg-white p-1.5"><x-logo class="h-full" /></span>
                <span class="font-display text-[17px] font-extrabold">GoTerapis</span>
            </div>
            <p class="mt-3.5 max-w-[280px] text-[13px] font-medium leading-relaxed text-white/50">Marketplace layanan terapi panggilan ke rumah. Terapis terverifikasi, harga jelas, dana ditahan sampai layanan selesai.</p>
        </div>

        <div>
            <h2 class="text-xs font-bold tracking-[.04em]">Layanan</h2>
            <ul class="mt-3.5 space-y-3 text-[13px] font-medium text-white/50">
                <li><a href="{{ route('cari', ['kategori' => 'pijat']) }}" class="hover:text-white">Pijat tradisional</a></li>
                <li><a href="{{ route('cari', ['kategori' => 'pijat']) }}" class="hover:text-white">Spot & Spa Massage</a></li>
                <li><a href="{{ route('cari', ['kategori' => 'bekam']) }}" class="hover:text-white">Bekam</a></li>
                <li><a href="{{ route('cari', ['kategori' => 'kretek']) }}" class="hover:text-white">Terapi olahraga</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-xs font-bold tracking-[.04em]">GoTerapis</h2>
            <ul class="mt-3.5 space-y-3 text-[13px] font-medium text-white/50">
                <li><a href="{{ route('home') }}#cara-kerja" class="hover:text-white">Cara kerja</a></li>
                <li><a href="{{ route('artikel.index') }}" class="hover:text-white">Jurnal kesehatan</a></li>
                <li><a href="{{ route('products.index') }}" class="hover:text-white">Toko</a></li>
                <li><a href="{{ route('register.therapist') }}" class="hover:text-white">Gabung jadi terapis</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-xs font-bold tracking-[.04em]">Bantuan</h2>
            <ul class="mt-3.5 space-y-3 text-[13px] font-medium text-white/50">
                @foreach (config('legal.documents') as $slug => $document)
                    <li><a href="{{ route('legal.show', $slug) }}" class="hover:text-white">{{ $document['title'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mx-auto mt-9 flex max-w-6xl flex-col gap-2 border-t border-white/10 pt-5 text-xs font-medium text-white/40 sm:flex-row sm:items-center sm:justify-between">
        <span>© {{ date('Y') }} GoTerapis</span>
        <span>Pembayaran diproses Midtrans</span>
    </div>
</footer>
