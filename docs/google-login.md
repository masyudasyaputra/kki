# Login Google

1. Buat OAuth client bertipe Web application di Google Cloud Console dan atur consent screen.
2. Isi variabel berikut di `.env` lokal/server (jangan commit rahasianya):

```dotenv
GOOGLE_CLIENT_ID=client-id-dari-google
GOOGLE_CLIENT_SECRET=client-secret-dari-google
GOOGLE_REDIRECT_URI=https://domain-aplikasi/auth/google/callback
```

Daftarkan URL `GOOGLE_REDIRECT_URI` yang sama persis sebagai Authorized redirect URI pada OAuth client. Gunakan domain dan protokol aplikasi yang sebenarnya; `APP_URL` juga harus sesuai. Untuk lingkungan pengujian Google, tambahkan akun penguji pada consent screen bila diperlukan.

3. Jalankan `php artisan config:clear`. Kolom `google_id` ditambahkan oleh migrasi `2026_10_25_000001_add_google_id_to_users_table`.
4. Uji masuk, pembatalan persetujuan Google, pendaftaran anggota baru, dan penghubungan akun lama lewat profil.

Login dan daftar dibuka melalui modal di halaman utama. Autentikasi Google berpindah ke halaman persetujuan Google, kemudian kembali ke aplikasi. Pengguna baru baru dibuat setelah formulir profil anggota dikirim. Nama dan email wajib, wilayah/dojo/sabuk serta kontak tambahan boleh belum diisi. Akun anggota baru tetap berstatus nonaktif sampai mengikuti proses aktivasi yang berlaku.

Akun lama dengan email yang sama tidak dihubungkan otomatis. Pengguna harus masuk dengan sandi, membuka profil, mengonfirmasi sandi, lalu memilih Hubungkan Google. Alur menggunakan state sekali pakai dan PKCE. Aplikasi tidak menyimpan access token Google.

Tanpa client ID dan secret, pilihan Google tetap terlihat dalam keadaan nonaktif; login dan pendaftaran melalui email tetap tersedia.
