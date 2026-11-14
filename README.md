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

## Foto dan file unggahan di production

- Arahkan document root web server ke folder `public`. Gunakan `APP_URL` sesuai domain dan HTTPS production, lalu jalankan `php artisan config:cache` setelah mengubah konfigurasi.
- File unggahan tersimpan di `storage/app/public`. Folder ini harus dapat ditulis oleh PHP dan dipertahankan antar deployment. Saat pindah server, salin file unggahan bersama database; Git tidak membawa foto pengguna.
- Jalankan `php artisan storage:link` agar web server dapat melayani file langsung. Jika hosting tidak mendukung symlink, route `/storage/{path}` akan melayani gambar dan PDF publik melalui Laravel. Permintaan URL yang tidak menunjuk file harus diteruskan ke `public/index.php`.
- URL unggahan memakai domain aplikasi, terpisah dari `ASSET_URL` yang dapat diarahkan ke CDN untuk aset statis. File privat tetap berada di disk `local` dan tidak dilayani route publik ini.
