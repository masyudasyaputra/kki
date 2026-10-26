# PP KKI

Aplikasi Pengurus Pusat KKI untuk keanggotaan dan administrasi nasional berbasis Laravel 12 dan Vite.
Dikembangkan dari https://github.com/masyudasyaputra/perguruan dengan riwayat Git asal dipertahankan.

## Menjalankan secara lokal

Persyaratan: PHP 8.2 atau lebih baru, Composer, MySQL, Node.js, dan npm.

```sh
composer install
cp .env.example .env
php artisan key:generate
# Buat database MySQL bernama kki dan sesuaikan kredensial di .env.
php artisan migrate
npm ci
npm run build
php artisan serve
```

Sesuaikan `APP_URL` di `.env` dengan alamat lokal yang digunakan. Untuk Laragon, gunakan `http://kki.test`.
Konfigurasi bawaan menggunakan MySQL dengan database terpisah bernama `kki`; migrasi proyek memerlukan MySQL.

Identitas aplikasi menggunakan PP KKI (Pengurus Pusat KKI), Kushin Ryu M Karate-Do Indonesia (KKI). Logo digunakan dari `public/images/logo-kki.png` di landing page, portal anggota, halaman autentikasi, dan favicon.
