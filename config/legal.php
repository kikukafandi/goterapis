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
            'title' => 'Syarat dan Ketentuan',
            'sections' => [
                ['Penggunaan GoTerapis', 'GoTerapis adalah marketplace yang mempertemukan pengguna dengan terapis independen. Pengguna wajib memberikan data yang benar, menjaga keamanan akun, berusia dan cakap melakukan perbuatan hukum, serta menggunakan layanan secara wajar.'],
                ['Peran platform dan terapis', 'Terapis menentukan layanan, harga, durasi, wilayah, jadwal, dan model layanan yang ditampilkan. GoTerapis memfasilitasi pencarian, pemesanan, pembayaran, komunikasi, ulasan, dan administrasi; bukan pemberi layanan terapi dan tidak membentuk hubungan kerja dengan terapis.'],
                ['Pemesanan dan pembayaran', 'Pesanan menunggu konfirmasi terapis sebelum pembayaran. Pembayaran elektronik diproses melalui Midtrans ketika gateway tersebut aktif. Pesanan yang tidak dibayar dalam jangka waktu konfigurasi dapat kedaluwarsa otomatis. Harga layanan, transportasi, dan biaya layanan ditampilkan sebelum pembayaran.'],
                ['Penyelesaian dan pendapatan terapis', 'Setelah layanan ditandai selesai, pendapatan bersih terapis menjadi tersedia 24 jam kemudian. Terapis meminta penarikan secara manual ke rekening terdaftar; admin meninjau dan memproses permintaan tanpa janji waktu penyelesaian tertentu.'],
                ['Larangan dan penegakan', 'Dilarang melakukan penipuan, pelecehan, layanan melanggar hukum, manipulasi ulasan atau pembayaran, penyalahgunaan data pribadi, dan aktivitas yang membahayakan orang lain. Akun atau akses dapat dibatasi untuk keamanan, kepatuhan, atau penyelidikan.'],
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
