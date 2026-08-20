<?php

$operatorName = env('LEGAL_OPERATOR_NAME', 'DRAFT: NAMA BADAN USAHA/OPERATOR');
$operatorAddress = env('LEGAL_OPERATOR_ADDRESS', 'DRAFT: ALAMAT LENGKAP OPERATOR');
$operatorEmail = env('LEGAL_OPERATOR_EMAIL', 'DRAFT: EMAIL PENGADUAN');

return [
    'operator_name' => $operatorName,
    'operator_address' => $operatorAddress,
    'operator_email' => $operatorEmail,
    'version' => env('LEGAL_VERSION', 'DRAFT'),
    'effective_date' => env('LEGAL_EFFECTIVE_DATE', 'DRAFT: YYYY-MM-DD'),

    'documents' => [
        'syarat-ketentuan' => [
            'title' => 'Ketentuan dan Syarat Terapis Go Terapis',
            'sections' => [
                ['Pendahuluan', 'Go Terapis merupakan platform yang mempertemukan pelanggan dengan terapis untuk mendapatkan layanan terapi sesuai jenis layanan, lokasi, waktu, dan harga yang tersedia pada platform. Dengan mendaftarkan diri dan menggunakan platform Go Terapis, terapis menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan berikut.'],
                ['A. Ketentuan Pendaftaran Terapis', "1. Terapis wajib memberikan data identitas yang benar, lengkap, dan dapat dipertanggungjawabkan.\n2. Terapis wajib menggunakan foto profil yang sesuai dengan identitas dirinya.\n3. Terapis dilarang menggunakan identitas, foto, atau data milik orang lain.\n4. Go Terapis berhak melakukan verifikasi terhadap data dan identitas terapis.\n5. Terapis bertanggung jawab menjaga keamanan akun, nomor telepon, kata sandi, dan informasi login.\n6. Akun terapis tidak boleh dipinjamkan, diperjualbelikan, atau digunakan oleh orang lain.\n7. Terapis wajib memiliki kemampuan dan keterampilan yang sesuai dengan jenis layanan terapi yang ditawarkan.\n8. Terapis wajib memberikan informasi layanan secara jujur, termasuk jenis terapi, durasi, harga, dan ketentuan layanan."],
                ['B. Etika dan Profesionalitas Terapis', "1. Terapis wajib bersikap sopan, ramah, profesional, dan menghormati pelanggan.\n2. Terapis wajib menghormati privasi, kenyamanan, batasan pribadi, serta martabat pelanggan.\n3. Terapis wajib menggunakan pakaian yang sopan dan sesuai untuk memberikan layanan terapi.\n4. Terapis wajib menjaga kebersihan diri, tangan, pakaian, serta peralatan terapi.\n5. Terapis wajib berkomunikasi dengan bahasa yang sopan dan tidak merendahkan, mengintimidasi, atau mengancam pelanggan.\n6. Terapis tidak diperkenankan melakukan tindakan atau komunikasi yang tidak berhubungan dengan layanan terapi dan dapat menimbulkan ketidaknyamanan.\n7. Terapis dilarang melakukan pelecehan seksual, tindakan asusila, prostitusi, atau tindakan lain yang bertentangan dengan hukum dan etika.\n8. Terapis wajib menghentikan layanan apabila terdapat situasi yang membahayakan keselamatan dirinya atau pelanggan."],
                ['C. Larangan Foto, Video, dan Rekaman', "1. Terapis dilarang memfoto atau merekam video pelanggan selama kegiatan terapi tanpa persetujuan pelanggan.\n2. Terapis dilarang merekam suara atau percakapan pelanggan tanpa persetujuan.\n3. Terapis dilarang mengambil foto atau video bagian tubuh pelanggan untuk kepentingan pribadi maupun promosi tanpa persetujuan yang bersangkutan.\n4. Penggunaan foto atau video pelanggan untuk keperluan promosi Go Terapis atau akun pribadi terapis harus mendapatkan persetujuan terlebih dahulu.\n5. Terapis wajib menjaga kerahasiaan seluruh foto, video, percakapan, dan informasi pribadi pelanggan yang diperoleh selama memberikan layanan."],
                ['D. Pelaksanaan Booking', "1. Terapis wajib memberikan pelayanan sesuai dengan jenis terapi, harga, durasi, waktu, dan lokasi yang disepakati melalui Go Terapis.\n2. Terapis yang menerima booking wajib berusaha hadir tepat waktu.\n3. Apabila terapis tidak dapat memenuhi booking, pembatalan harus dilakukan melalui mekanisme yang tersedia pada platform.\n4. Terapis dilarang menerima booking tetapi dengan sengaja tidak memberikan pelayanan tanpa alasan yang dapat dipertanggungjawabkan.\n5. Terapis wajib memberitahukan kepada pelanggan apabila terjadi keterlambatan atau perubahan keadaan yang memengaruhi pelayanan.\n6. Terapis tidak diperkenankan meminta pelanggan membayar biaya tambahan yang tidak tercantum atau tidak disepakati sebelumnya."],
                ['E. Larangan Menghindari Transaksi Platform', "1. Terapis dilarang mengalihkan transaksi yang diperoleh melalui Go Terapis ke luar platform dengan tujuan menghindari biaya layanan, komisi, atau mekanisme transaksi Go Terapis.\n2. Terapis dilarang meminta pelanggan membatalkan booking di Go Terapis kemudian melakukan pembayaran langsung kepada terapis untuk menghindari sistem platform.\n3. Terapis dilarang menggunakan data pelanggan yang diperoleh melalui Go Terapis untuk mengarahkan pelanggan melakukan transaksi di luar platform dengan tujuan menghindari ketentuan platform.\n4. Komunikasi antara terapis dan pelanggan di luar aplikasi, termasuk melalui WhatsApp atau telepon, diperbolehkan sepanjang tidak digunakan untuk menghindari ketentuan transaksi Go Terapis.\n5. Untuk booking yang dilakukan melalui Go Terapis, terapis wajib mengikuti mekanisme transaksi yang telah ditetapkan oleh platform."],
                ['F. Keselamatan dan Batasan Layanan', "1. Terapis wajib memberikan layanan sesuai dengan kemampuan dan kompetensinya.\n2. Terapis tidak diperbolehkan memberikan tindakan yang berada di luar kemampuan atau keahliannya.\n3. Terapis wajib memperhatikan kondisi pelanggan sebelum dan selama terapi.\n4. Apabila kondisi pelanggan dinilai tidak memungkinkan untuk diberikan terapi, terapis berhak menolak atau menghentikan layanan dengan alasan yang dapat dipertanggungjawabkan.\n5. Terapis dilarang memberikan janji atau klaim kesembuhan pasti kepada pelanggan.\n6. Terapis wajib menyarankan pelanggan mendapatkan pemeriksaan tenaga kesehatan apabila terdapat kondisi yang memerlukan pemeriksaan atau penanganan medis."],
                ['G. Privasi dan Kerahasiaan', "1. Terapis wajib menjaga kerahasiaan seluruh informasi pelanggan yang diperoleh melalui Go Terapis.\n2. Informasi pelanggan tidak boleh dijual, disebarkan, dipublikasikan, atau diberikan kepada pihak lain tanpa dasar yang sah atau persetujuan pelanggan.\n3. Terapis dilarang menggunakan foto, nomor telepon, alamat, atau informasi pribadi pelanggan untuk kepentingan yang tidak berkaitan dengan layanan.\n4. Terapis wajib menjaga keamanan data pelanggan yang tersimpan pada perangkat pribadi."],
                ['H. Penilaian dan Ulasan', "1. Pelanggan berhak memberikan rating dan ulasan terhadap layanan yang diterimanya.\n2. Terapis tidak diperkenankan memaksa, mengancam, atau memberikan imbalan kepada pelanggan agar memberikan rating atau ulasan tertentu.\n3. Terapis dilarang membuat ulasan palsu atau memanipulasi sistem penilaian.\n4. Go Terapis dapat melakukan tindakan terhadap aktivitas yang terindikasi melakukan manipulasi rating atau ulasan."],
                ['I. Larangan Penyalahgunaan Platform', "Terapis dilarang:\n1. Membuat akun palsu atau akun ganda untuk tujuan manipulasi.\n2. Membuat booking atau transaksi palsu.\n3. Memanipulasi sistem, rating, promosi, voucher, atau fitur Go Terapis.\n4. Menggunakan platform untuk kegiatan ilegal.\n5. Menggunakan platform untuk menawarkan layanan seksual atau layanan yang tidak sesuai dengan tujuan Go Terapis.\n6. Menggunakan platform untuk melakukan penipuan, pemerasan, intimidasi, atau tindakan merugikan pihak lain.\n7. Melakukan tindakan yang dapat merusak keamanan, reputasi, atau operasional Go Terapis."],
                ['J. Pembayaran dan Biaya Layanan', "1. Terapis wajib mengikuti mekanisme pembayaran yang ditetapkan oleh Go Terapis.\n2. Biaya layanan atau komisi platform akan mengikuti ketentuan yang berlaku pada saat transaksi.\n3. Terapis tidak diperkenankan memanipulasi harga atau transaksi untuk menghindari biaya layanan platform.\n4. Informasi mengenai pendapatan, potongan, komisi, dan pembayaran dapat ditampilkan melalui sistem Go Terapis."],
                ['K. Sanksi Pelanggaran', "Apabila terapis terbukti melanggar ketentuan, Go Terapis dapat memberikan tindakan sesuai tingkat pelanggaran, antara lain:\n1. Teguran.\n2. Peringatan tertulis.\n3. Pembatasan sementara terhadap akun atau fitur tertentu.\n4. Pembatalan atau penolakan booking.\n5. Penangguhan akun.\n6. Penghapusan akun.\n7. Pemblokiran permanen.\n8. Pelaporan kepada pihak berwenang apabila terdapat dugaan pelanggaran hukum.\nTingkat sanksi dapat disesuaikan dengan jenis, tingkat keseriusan, dampak, dan riwayat pelanggaran."],
                ['L. Perubahan Ketentuan', "1. Go Terapis dapat melakukan perubahan, penambahan, atau pembaruan terhadap ketentuan dan kebijakan platform.\n2. Perubahan ketentuan akan diinformasikan melalui media yang tersedia pada platform apabila diperlukan.\n3. Dengan tetap menggunakan layanan Go Terapis setelah ketentuan diperbarui, terapis dianggap telah menyetujui ketentuan yang berlaku."],
                ['M. Persetujuan Terapis', 'Dengan melakukan pendaftaran dan menggunakan Go Terapis, terapis menyatakan bahwa: Saya telah membaca, memahami, dan menyetujui seluruh Ketentuan dan Syarat Terapis Go Terapis. Saya bersedia memberikan layanan secara profesional, menjaga etika dan privasi pelanggan, mematuhi mekanisme transaksi Go Terapis, serta menerima sanksi apabila terbukti melanggar ketentuan yang berlaku. Go Terapis berhak mengambil tindakan yang diperlukan untuk menjaga keamanan, kenyamanan, kepercayaan pelanggan, terapis, dan keberlangsungan platform.'],
            ],
        ],
        'kebijakan-privasi' => [
            'title' => 'Kebijakan Privasi',
            'sections' => [
                ['Data yang diproses', 'Kami memproses data akun dan kontak, profil, data transaksi dan pembayaran, pesanan, ulasan, percakapan, notifikasi, alamat serta koordinat pesanan, lokasi terapis saat menuju pelanggan, data perangkat/log teknis, dan dokumen verifikasi terapis seperti KTP, rekening, sertifikat, surat pengalaman, STPT, atau foto tempat praktik bila disampaikan.'],
                ['Tujuan dan dasar penggunaan', 'Data digunakan untuk menyediakan dan mengamankan akun, mempertemukan pengguna dan terapis, menjalankan pesanan serta pembayaran, verifikasi terapis, komunikasi, pencegahan penyalahgunaan, dukungan, pengaduan, pembukuan, dan kewajiban hukum. Data sensitif hanya digunakan sejauh diperlukan untuk verifikasi dan administrasi layanan.'],
                ['Penerima data', 'Data relevan dapat dibagikan antara pengguna dan terapis untuk melaksanakan pesanan. Midtrans memproses data pembayaran sesuai layanannya. Gateway WhatsApp dapat menerima nomor telepon dan isi notifikasi operasional. Penyedia hosting, basis data, cache/realtime, dan pihak berwenang dapat menerima data sejauh diperlukan atau diwajibkan hukum. Kami tidak menyatakan menjual data pribadi.'],
                ['Lokasi, realtime, dan chat', 'Koordinat pelanggan digunakan untuk layanan panggilan. Saat terapis berstatus menuju lokasi, posisi terapis dikirim berkala dan disimpan sementara untuk menampilkan jarak serta memverifikasi kedatangan/mulai layanan. Percakapan pesanan disimpan agar pihak terkait dapat berkomunikasi dan menangani sengketa.'],
                ['Penyimpanan dan keamanan', 'Avatar disimpan pada media publik agar dapat tampil di profil. Dokumen verifikasi baru disimpan pada penyimpanan privat dan hanya dapat diakses admin berwenang melalui aplikasi. Tidak ada sistem yang sepenuhnya bebas risiko; kami menerapkan pembatasan akses yang sesuai dengan fungsi saat ini tanpa menjanjikan keamanan mutlak atau masa simpan tertentu.'],
                ['Hak dan pertanyaan', 'Permintaan akses, koreksi, atau penghapusan dapat diajukan melalui kanal pengaduan. Pemenuhan bergantung pada verifikasi identitas, kemampuan teknis, kepentingan pihak lain, serta kewajiban penyimpanan transaksi atau hukum yang berlaku.'],
            ],
        ],
        'pembatalan-pengembalian' => [
            'title' => 'Pembatalan dan Pengembalian Dana',
            'sections' => [
                ['Sebelum pembayaran', 'Pesanan yang masih menunggu konfirmasi atau pembayaran dapat dibatalkan tanpa pengembalian dana karena belum ada dana yang diproses.'],
                ['Setelah pembayaran', 'Pembatalan pelanggan hanya tersedia sebelum layanan berjalan. Jika dilakukan lebih dari '.config('goterapis.cancel_free_hours').' jam sebelum jadwal, harga layanan dan transportasi dapat dikembalikan; biaya layanan platform tidak dikembalikan. Jika lebih dekat dari batas tersebut, kompensasi terapis sebesar '.config('goterapis.cancel_compensation_percent').'% dari harga layanan dapat dipotong dan sisanya dikembalikan bersama biaya transportasi, sementara biaya layanan platform tetap tidak dikembalikan.'],
                ['Pemrosesan', 'Pengembalian diproses melalui gateway pembayaran aktif. Jika gateway menolak atau gagal, pesanan tidak ditandai batal agar dana tidak hilang dari pencatatan. Waktu dana masuk bergantung pada Midtrans, bank, atau metode pembayaran dan tidak kami janjikan.'],
                ['Sengketa dan perubahan aturan', 'Laporkan ketidaksesuaian melalui kanal pengaduan dengan bukti yang relevan. Nilai batas waktu dan persentase mengikuti konfigurasi yang berlaku saat tindakan pembatalan diproses dan dapat berubah untuk pesanan berikutnya.'],
            ],
        ],
        'penafian-layanan' => [
            'title' => 'Penafian Layanan',
            'sections' => [
                ['Bukan layanan medis', 'GoTerapis bukan rumah sakit, klinik, layanan darurat, atau penyedia diagnosis dan pengobatan medis. Informasi profil, artikel, ulasan, dan status verifikasi bukan rekomendasi medis ataupun jaminan hasil, kompetensi, legalitas praktik, keamanan, atau kecocokan terapis.'],
                ['Keputusan pengguna', 'Tanyakan kualifikasi, metode, kontraindikasi, kebersihan, dan risiko kepada terapis. Hentikan layanan bila tidak nyaman. Untuk gejala berat, kehamilan, kondisi medis, penggunaan obat, cedera, atau keadaan darurat, hubungi tenaga kesehatan yang berwenang atau layanan darurat setempat.'],
                ['Ketersediaan teknologi', 'Lokasi, chat, notifikasi WhatsApp, realtime, pembayaran, dan layanan pihak ketiga dapat terlambat atau tidak tersedia. Jangan mengandalkan fitur aplikasi sebagai satu-satunya sarana komunikasi darurat.'],
            ],
        ],
        'panduan-komunitas' => [
            'title' => 'Panduan Komunitas',
            'sections' => [
                ['Saling menghormati', 'Pengguna dan terapis wajib menghormati persetujuan, batas pribadi, privasi, keselamatan, serta tidak mendiskriminasi, melecehkan, mengancam, atau melakukan tindakan seksual dan kekerasan.'],
                ['Jujur dan profesional', 'Gunakan identitas serta dokumen yang sah, tampilkan harga dan kemampuan secara jujur, jaga kebersihan, datang sesuai kesepakatan, dan jangan membuat klaim kesembuhan atau kualifikasi yang menyesatkan.'],
                ['Komunikasi dan ulasan', 'Gunakan chat untuk kebutuhan pesanan. Jangan mengirim spam, materi ilegal, data orang lain tanpa izin, atau mencoba memanipulasi ulasan. Ulasan harus berdasarkan pengalaman nyata.'],
                ['Pelaporan', 'Laporkan perilaku atau layanan bermasalah melalui kanal pengaduan. Kami dapat meninjau data terkait, meminta informasi, membatasi akun, atau meneruskan perkara kepada pihak berwenang bila diperlukan.'],
            ],
        ],
        'cookie' => [
            'title' => 'Kebijakan Cookie',
            'sections' => [
                ['Cookie esensial saja', 'Aplikasi saat ini hanya menggunakan cookie dan penyimpanan browser yang diperlukan untuk sesi login, keamanan CSRF, preferensi teknis dasar, dan fungsi aplikasi. Kami tidak menyatakan menggunakan cookie iklan, pelacakan lintas situs, atau analitik non-esensial.'],
                ['Pengendalian browser', 'Memblokir atau menghapus cookie esensial dapat membuat login, formulir, pembayaran, dan fitur lain tidak bekerja. Jika kelak cookie non-esensial ditambahkan, kebijakan dan mekanisme persetujuan harus diperbarui sebelum digunakan.'],
            ],
        ],
        'kontak-pengaduan' => [
            'title' => 'Kontak dan Pengaduan',
            'sections' => [
                ['Operator', 'Layanan ini dioperasikan oleh '.$operatorName.', beralamat di '.$operatorAddress.'.'],
                ['Menghubungi kami', 'Kirim pertanyaan privasi, pengaduan pesanan, keamanan, atau permintaan hak data ke '.$operatorEmail.'. Sertakan identitas akun, nomor pesanan bila ada, uraian kejadian, dan bukti secukupnya; jangan mengirim kata sandi atau data pembayaran lengkap.'],
                ['Penanganan', 'Kami dapat memverifikasi identitas, meminta informasi tambahan, meninjau catatan terkait, dan menghubungi pihak dalam pesanan. Kami tidak menjanjikan hasil atau waktu penyelesaian tertentu karena penanganan bergantung pada sifat masalah dan pihak terkait.'],
            ],
        ],
    ],
];
