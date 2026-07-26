# Rencana Notifikasi WhatsApp GoTerapis

## Tujuan

Memudahkan pelanggan dan terapis menerima informasi penting tanpa harus rutin membuka aplikasi atau email.

## Alur Pesanan Baru

1. Pelanggan membuat pesanan.
2. Laravel menyimpan pesanan dan memasukkan notifikasi ke queue.
3. Gateway WhatsApp berbasis `whatsapp-web.js` mengirim ringkasan pesanan kepada terapis.
4. Pesan WhatsApp menyediakan satu tautan **Buka pesanan**.
5. Tautan ditandatangani, memiliki masa berlaku, dan hanya mengarahkan pengguna ke aplikasi.
6. Jika belum masuk, terapis melakukan login lalu otomatis kembali ke detail pesanan.
7. Terapis menekan tombol besar **Terima** atau **Tolak** di aplikasi.
8. Pelanggan menerima pembaruan melalui inbox aplikasi, Reverb, dan WhatsApp.
9. Scheduler mengirim pengingat jika pesanan belum ditanggapi dan membatalkannya setelah batas waktu.

## Kanal Notifikasi

- Inbox aplikasi sebagai sumber informasi utama dan riwayat notifikasi.
- Reverb untuk pembaruan instan saat aplikasi terbuka.
- WhatsApp satu arah untuk kejadian penting.
- Email hanya sebagai fallback jika kelak dibutuhkan.

## Notifikasi Penting

- Pesanan baru untuk terapis.
- Pesanan diterima atau ditolak.
- Pembayaran berhasil.
- Pengingat jadwal H-1 dan satu jam sebelumnya.
- Terapis mulai perjalanan.
- Terapis tiba.
- Layanan dimulai dan diselesaikan.

## Arsitektur

`Event Laravel → Queue Job → Gateway HTTP → whatsapp-web.js → WhatsApp`

Gateway dijalankan sebagai layanan terpisah dan menyediakan:

- Endpoint pengiriman yang dilindungi token.
- Validasi nomor dan payload.
- Queue, retry, dan rate limit.
- Status sesi dan QR untuk autentikasi WhatsApp Web.
- Health check.
- Pencatatan status pengiriman tanpa menyimpan data sensitif berlebihan.

## Aturan Keamanan

- Kegagalan WhatsApp tidak boleh menggagalkan transaksi utama.
- Tautan WhatsApp tidak langsung menerima atau menolak pesanan melalui request `GET`.
- Aksi tetap memerlukan halaman konfirmasi di aplikasi.
- Gunakan signed URL yang kedaluwarsa dan tetap terikat pada pengguna/pesanan.
- Jangan mengirim PIN layanan, detail pembayaran sensitif, atau alamat lengkap melalui gateway nonresmi.
- Gunakan nomor WhatsApp khusus operasional.

## Tahapan Pengembangan

1. Buat inbox notifikasi aplikasi dan status sudah dibaca.
2. Terbitkan event untuk perubahan penting pada pesanan dan pembayaran.
3. Tambahkan queue job serta pencatatan percobaan pengiriman.
4. Bangun gateway kecil berbasis `whatsapp-web.js`.
5. Integrasikan notifikasi pesanan baru dengan tautan **Buka pesanan**.
6. Tambahkan notifikasi perubahan status dan pengingat terjadwal.
7. Tambahkan retry, rate limit, health monitoring, dan fallback.
8. Evaluasi migrasi ke WhatsApp Cloud API jika stabilitas atau skala membutuhkan.

## Batas MVP

Belum mencakup tombol interaktif asli WhatsApp, broadcast pemasaran, balasan bot, editor template kompleks, atau pelacakan lokasi background. Tombol interaktif asli ditambahkan ketika memakai WhatsApp Business API resmi.
