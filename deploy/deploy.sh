#!/usr/bin/env bash
# Deploy in-place dari origin/master. Dipanggil manual atau oleh timer auto-deploy
# lewat /usr/local/bin/goterapis-deploy yang hanya meneruskan ke berkas ini.
#
# Seluruh isi dibungkus main() karena `git reset --hard` menimpa berkas ini justru
# saat ia sedang berjalan; bash harus membaca sampai habis sebelum satu perintah pun
# dieksekusi, kalau tidak versi lama dan baru bisa tercampur di tengah jalan.
#
# ponytail: tanpa rilis ber-symlink — situs maintenance ~1-3 menit saat build.
#           Naik ke skema symlink kalau downtime itu mulai terasa.
set -Eeuo pipefail

# Kegagalan di tengah deploy tidak boleh menayangkan aplikasi yang separuh jadi.
# ponytail: situs sengaja ditinggal di mode maintenance dan timer TIDAK akan
#           memperbaikinya sendiri (HEAD sudah sama dengan origin/master), jadi
#           kegagalan wajib ditangani orang. Halaman maintenance jauh lebih baik
#           daripada 500 di seluruh situs.
gagal() {
    echo "Deploy GAGAL — situs ditahan di mode maintenance." >&2
    echo "Perbaiki penyebabnya lalu jalankan ulang: sudo goterapis-deploy" >&2
}

main() {
    trap gagal ERR
    cd /var/www/goterapis

    git fetch --quiet origin master
    local old new
    old=$(git rev-parse HEAD)
    new=$(git rev-parse origin/master)
    [ "$old" = "$new" ] && { echo "Sudah terbaru ($old)."; return 0; }

    echo "Deploy $old -> $new"
    php artisan down --retry=60 || true

    git reset --hard "$new"

    # Pasang ulang dependensi hanya kalau lockfile-nya ikut berubah.
    git diff --quiet "$old" "$new" -- composer.lock || composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    git diff --quiet "$old" "$new" -- package-lock.json || npm ci
    git diff --quiet "$old" "$new" -- whatsapp-gateway/package-lock.json || (cd whatsapp-gateway && PUPPETEER_SKIP_DOWNLOAD=true npm ci --omit=dev)

    # Config cache dibuat sebelum build: kalau build gagal, aplikasi tetap punya
    # konfigurasi yang sah dan cuma memakai aset lama.
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # npm sesekali memasang binernya tanpa bit executable; ini yang mematikan
    # deploy 16 Agustus 2026 (sh: vite: Permission denied).
    chmod -f +x node_modules/.bin/* node_modules/*/bin/*.js 2>/dev/null || true
    npm run build

    sudo chown -R goterapis:www-data /var/www/goterapis
    # chown di atas mengambil kepemilikan folder yang dibuat PHP-FPM saat runtime
    # (unggahan, dokumen terapis). Modenya 755/700 dari umask PHP, jadi setelah
    # pemiliknya jadi goterapis, www-data tinggal punya hak grup — dan kehilangan
    # akses tulis/baca. Itu yang mematikan unggahan & pratinjau dokumen 17 Agustus
    # 2026. g+rwX mengembalikannya, g+s membuat folder baru mewarisi grup www-data.
    sudo chmod -R g+rwX /var/www/goterapis/storage /var/www/goterapis/bootstrap/cache
    sudo find /var/www/goterapis/storage -type d -exec chmod g+s {} +
    # PHP-FPM jalan sebagai www-data dan harus bisa membaca .env kalau config cache
    # hilang — tanpa ini, satu deploy gagal membuat seluruh situs 500.
    sudo chmod 640 /var/www/goterapis/.env
    sudo systemctl restart php8.4-fpm goterapis-queue goterapis-reverb

    php artisan up

    # Deploy hanya dianggap selesai kalau aplikasi benar-benar menjawab.
    if ! curl -fsS -o /dev/null --max-time 20 https://goterapis.com/up; then
        echo "Aplikasi tidak sehat setelah deploy — dikembalikan ke mode maintenance." >&2
        php artisan down --retry=60 || true
        return 1
    fi

    echo "Deploy selesai: $new"
}

main "$@"
