# Yang Perlu Diperbaiki

Dicatat 16 Agustus 2026, sebagian besar temuan saat rilis dan insiden deploy hari itu.
Urutan menurun berdasarkan risiko.

## 1. Tidak ada backup database

`systemctl list-timers` di server hanya punya `dpkg-db-backup.timer` bawaan Ubuntu —
tidak ada satu pun tugas yang mencadangkan MySQL. Deploy menjalankan `migrate --force`
tanpa jaring pengaman apa pun; satu migrasi salah dan data pesanan, saldo, serta
pendapatan mitra hilang tanpa jalan pulang.

Tindakan: timer harian `mysqldump` ke direktori di luar `/var/www`, plus salinan ke
penyimpanan lain. Uji pemulihannya sekali, karena backup yang belum pernah dipulihkan
belum tentu backup.

## 2. Kegagalan deploy tidak memberi kabar

`deploy/deploy.sh` sengaja menahan situs di mode maintenance kalau ada langkah yang
gagal, dan timer tidak akan memperbaikinya sendiri (HEAD sudah sama dengan
origin/master). Artinya situs bisa diam di halaman maintenance sampai ada orang yang
kebetulan membukanya.

Tindakan: kirim pesan ke nomor admin dari fungsi `gagal()` lewat gateway WhatsApp yang
sudah jalan.

## 3. Sesi WhatsApp belum stabil

Gateway `whatsapp-web.js` pernah `LOGOUT` sendiri dan belum ada reconnect maupun
monitoring. Semua pengingat, notifikasi pesanan baru, dan OTP penarikan lewat jalur ini —
kalau mati, terapis tidak tahu ada pesanan dan pesanan hangus otomatis.

Tindakan: reconnect otomatis, alert saat sesi putus, health check yang dipantau.

## 4. `www` melayani situs sendiri

`https://www.goterapis.com` menjawab 200, bukan 301 ke apex. Dua akibatnya: konten
terduplikasi di mata mesin pencari, dan login Google bisa gagal `Invalid state` kalau
pengguna memulai dari `www` sementara callback mendarat di apex.

Tindakan: redirect permanen `www` → apex di nginx.

## 5. Blokir dua arah belum ada

PRD menjanjikan pengguna bisa memblokir terapis dan sebaliknya. Yang ada baru
`EnsureUserIsNotBlocked`, yaitu penangguhan akun oleh admin. Model `Block` sudah ada
tapi tidak dipakai sama sekali.

## 6. Terapis mangkir tidak berkonsekuensi

Pesanan yang tak dijawab sekarang dibatalkan otomatis setelah dua jam, tapi terapisnya
tidak menanggung apa pun. Tanpa konsekuensi, pembatalan otomatis cuma memindahkan
kerugian ke pelanggan.

Tindakan: putuskan kebijakannya dulu (turun peringkat pencarian, jeda menerima pesanan,
atau ketersediaan dimatikan otomatis), baru dikodekan.

## 7. Status `accepted` sudah mati

`Order::BLOCKING_STATUSES` dan dua filter di `TherapistDashboardController` serta
`TherapistOrderController` masih menyebut status `accepted`, padahal tidak ada satu pun
`changeStatus()` yang pernah menetapkannya. Sisa alur lama yang menyesatkan pembaca.

Tindakan: hapus dari ketiga tempat itu.

## 8. Bundel `artikel-editor` 1 MB

Build memperingatkan chunk 1.080 kB (284 kB gzip) untuk editor artikel yang hanya
dipakai admin, tapi ikut dibangun setiap deploy.

Tindakan: `import()` dinamis supaya tidak membebani halaman lain.

## 9. Penanda pengingat masih di cache

`Order::remind()` memakai `Cache::add` sebagai penanda "sudah dikirim". Cukup untuk
sekarang, tapi riwayat pengingat tidak bisa diaudit dan hilang kalau cache dibersihkan —
paling buruk berarti satu pengingat terkirim dua kali.

Tindakan: pindahkan ke kolom di `orders` kalau riwayatnya mulai dibutuhkan.

## Belum dikerjakan sama sekali (Tahap 4–5 PRD)

Keranjang, checkout, stok, dan pengiriman untuk toko; marketplace penjual pihak ketiga;
iklan produk; paket langganan terapis. Semuanya menunggu volume transaksi pemesanan,
sesuai urutan yang PRD sendiri sarankan.
