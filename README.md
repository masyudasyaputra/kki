# KKI

Aplikasi keanggotaan dan administrasi KKI berbasis Laravel 12 dan Vite.
Dikembangkan dari https://github.com/masyudasyaputra/perguruan dengan riwayat Git asal dipertahankan.

## Menjalankan secara lokal

Persyaratan: PHP 8.2 atau lebih baru, Composer, Node.js, dan npm.

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
php artisan serve
```

Sesuaikan `APP_URL` di `.env` dengan alamat lokal yang digunakan. Untuk Laragon, gunakan `http://kki.test`.
Konfigurasi bawaan menggunakan SQLite. Untuk MySQL, atur koneksi dan gunakan database terpisah bernama `kki`.

Logo dan foto bawaan proyek asal masih digunakan; siapkan aset KKI untuk menggantinya.
