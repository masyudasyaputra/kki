# Jabatan dan hak akses

Jabatan disimpan dalam `users.positions`; hak tambahan dalam `users.roles`. Kolom `role` adalah jabatan utama untuk kompatibilitas modul yang sudah ada. Satu akun dapat memiliki beberapa jabatan dan hak tambahan.

## Jabatan

| Jabatan | Fungsi |
| --- | --- |
| Super Admin | Mengelola akun, jabatan, hak akses, penugasan verifikator, dan riwayat akses |
| Pengurus PB | Mengelola administrasi nasional dan penugasan verifikator |
| Dewan Guru Pusat | Memantau ujian, nilai, dan peserta seluruh daerah |
| Pengprov | Mengelola provinsinya |
| Pengcab | Mengelola kabupaten/kotanya |
| Admin Dojo | Mengelola dojo dan anggotanya |
| Anggota | Mengakses data pribadinya |

## Hak tambahan

| Kode | Hak dan cakupan |
| --- | --- |
| `verifikator_anggota_pb` | Memverifikasi keanggotaan seluruh daerah |
| `verifikator_pengprov` | Memverifikasi keanggotaan dalam provinsi akun |
| `verifikator_pengcab` | Memverifikasi keanggotaan dalam provinsi dan kabupaten/kota akun |
| `panitia_ujian` | Mengisi peserta, melengkapi nilai, dan mengirim hasil pada sesi yang ditugaskan |
| `penguji` | Mengisi nilai pada sesi yang ditugaskan |
| `verifikator_ujian_pb` | Memverifikasi sesi ujian yang ditugaskan pusat, dari daerah mana pun |
| `pengelola_sertifikat_pb` | Mengelola template, penerbitan, dan pengiriman semua sertifikat DAN/KYU oleh PB |

PB, Super Admin, dan Dewan Guru tidak otomatis memiliki hak verifikasi atau sertifikat. Hak tambahan harus dipilih di Manajemen User. Dewan Guru maupun pengguna yang bukan Dewan Guru dapat diberi hak Verifikator Ujian PB.

Admin provinsi hanya dapat mengelola akun daerah dalam provinsinya. Mereka tidak dapat memberikan jabatan pusat, hak verifikator pusat/ujian PB, atau pengelolaan sertifikat PB. Wilayah kabupaten/kota dan dojo divalidasi terhadap induknya.

## Penugasan dan proses ujian

1. PB/pengurus daerah membuat sesi. Panitia dan penguji ditugaskan melalui **Penguji & Panitia**.
2. Panitia melengkapi peserta serta nilai, lalu mengirim hasil ke pusat. Data dikunci selama pengajuan.
3. PB/Super Admin membuka **Pengajuan Hasil Ujian**, lalu memilih pengguna yang memiliki hak Verifikator Ujian PB. Penugasan dapat dilakukan sebelum atau setelah pengiriman.
4. Verifikator yang ditugaskan menyetujui atau meminta perbaikan dengan catatan. Panitia, penguji, dan pengirim tidak dapat menjadi verifikator sesi yang sama. Akun yang sedang memakai Login as tidak dapat memverifikasi.
5. Setelah disetujui, Pengelola Sertifikat PB menerbitkan dan mengirim sertifikat. Daerah dapat memantau status; anggota mengunduh sertifikat yang diterimanya.

Penguji/verifikator ujian tanpa penugasan tidak dapat membuka sesi tersebut. Dewan Guru dan PB dapat memantau nasional, tetapi tetap memerlukan hak dan penugasan terpisah untuk memberikan keputusan. Pencabutan hak verifikator segera menghentikan akses walaupun penugasan lama masih tercatat.

Riwayat keputusan tetap tersimpan pada verifikasi anggota/ujian. Perubahan akun, jabatan, hak, serta penugasan dicatat pada **Riwayat Hak Akses**.

## Migrasi akun lama

Jalankan `php artisan migrate`.

- `verifikator_pb` menjadi **Verifikator Keanggotaan Pusat**, tanpa hak ujian otomatis.
- `verifikator_dewan_guru` menjadi jabatan **Dewan Guru Pusat** + **Verifikator Keanggotaan Pusat**.
- `admin_pengprov` / `admin_pengcab` dinormalisasi menjadi Pengprov / Pengcab.
- Provinsi, kabupaten/kota, dojo, password, dan sertifikat yang sudah terbit dipertahankan.
- Akun PB yang sebelumnya mengoperasikan sertifikat diberi hak **Pengelola Sertifikat PB** secara eksplisit saat migrasi untuk menjaga kelanjutan pekerjaan; hak ini dapat dicabut. Akun baru tidak memperolehnya otomatis.
- Tidak ada penugasan verifikator ujian otomatis. PB harus memilih hak dan penugasan sesi setelah migrasi.
- Ujian lama yang sudah selesai tetap dapat menggunakan alur sertifikat. Untuk ujian baru, status selesai hanya dihasilkan setelah persetujuan verifikator.
