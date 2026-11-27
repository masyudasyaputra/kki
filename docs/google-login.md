# Login Google

1. Buat OAuth client bertipe Web application di Google Cloud Console dan atur consent screen.
2. Isi variabel berikut di `.env` lokal/server (jangan commit rahasianya):

```dotenv
GOOGLE_CLIENT_ID=client-id-dari-google
GOOGLE_CLIENT_SECRET=client-secret-dari-google
APP_URL=https://ppkki.org
GOOGLE_REDIRECT_URI=https://ppkki.org/auth/google/callback
```

Daftarkan URL `GOOGLE_REDIRECT_URI` yang sama persis sebagai Authorized redirect URI pada OAuth client. Gunakan domain dan protokol aplikasi yang sebenarnya; `APP_URL` juga harus sesuai. Untuk lingkungan pengujian Google, tambahkan akun penguji pada consent screen bila diperlukan.

Pengaturan Google Auth Platform untuk domain produksi:
- Authorized domain pada Branding: `ppkki.org`.
- Application home page: `https://ppkki.org`.
- Privacy policy: `https://ppkki.org/kebijakan-privasi`.
- OAuth client: Web application.
- Authorized redirect URI: `https://ppkki.org/auth/google/callback`.
- Jika menggunakan kolom Authorized JavaScript origins: `https://ppkki.org`.

Isi Client ID dan Client Secret di `.env` server yang melayani domain tersebut. Perubahan `.env` di komputer pengembangan tidak mengubah konfigurasi server. Mulai alur login dari `https://ppkki.org` agar sesi OAuth dan callback berada pada host yang sama. Untuk anggota umum, gunakan audience External; tambahkan test users selama pengujian sebelum mengubah status publikasi sesuai kebutuhan aplikasi.

3. Jalankan `php artisan config:clear`. Kolom `google_id` ditambahkan oleh migrasi `2026_10_25_000001_add_google_id_to_users_table`.
4. Uji masuk, pembatalan persetujuan Google, pendaftaran anggota baru, dan penghubungan akun lama lewat profil.

Login dan daftar dibuka melalui modal di halaman utama. Autentikasi Google berpindah ke halaman persetujuan Google, kemudian kembali ke aplikasi. Pengguna baru baru dibuat setelah formulir profil anggota dikirim. Nama dan email wajib, wilayah/dojo/sabuk serta kontak tambahan boleh belum diisi. Akun anggota baru tetap berstatus nonaktif sampai mengikuti proses aktivasi yang berlaku.

Akun lama dengan email yang sama tidak dihubungkan otomatis. Pengguna harus masuk dengan sandi, membuka profil, mengonfirmasi sandi, lalu memilih Hubungkan Google. Alur menggunakan state sekali pakai dan PKCE. Aplikasi tidak menyimpan access token Google.

Tanpa client ID dan secret, pilihan Google tetap terlihat dalam keadaan nonaktif; login dan pendaftaran melalui email tetap tersedia.
