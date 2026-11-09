# Wilayah, data anggota, dan verifikasi hasil ujian

- Menu **Kabupaten & Kecamatan** tersedia untuk PB serta admin provinsi. Admin provinsi hanya dapat melihat dan menambah wilayah provinsinya. Kabupaten/kota memiliki daftar kecamatan masing-masing.
- Migrasi menyalin nama kecamatan yang sudah ada di data anggota ke master wilayah tanpa menghapus data anggota. Daftar nasional lengkap belum diimpor; wilayah yang belum tersedia dapat ditambahkan melalui menu tersebut.
- Pendaftaran anggota, pendaftaran kolektif, dan profil menyediakan nama dojo serta pelatih awal. Kecamatan menampilkan saran sesuai kabupaten/kota; nama lama tetap dapat digunakan.
- Buat akun dengan role **Panitia Ujian**, lalu tugaskan pada sesi lewat halaman **Penugasan Penguji & Panitia Ujian**. Panitia hanya dapat mengakses sesi yang ditugaskan kepadanya.
- Panitia mengisi peserta serta semua materi nilai yang diperlukan untuk DAN/KYU, lalu memilih **Kirim Hasil ke Pusat** pada halaman penilaian. Tombol ini menggantikan finalisasi langsung.
- Hak **Verifikator Ujian PB** (`verifikator_ujian_pb`) memeriksa pengajuan melalui **Pengajuan Hasil Ujian**, setelah ditugaskan pada sesi tersebut. Verifikator dapat menyetujui atau mengembalikan untuk perbaikan dengan catatan wajib. Pengirim, panitia, dan penguji tidak dapat memverifikasi sesi yang sama.
- Selama menunggu verifikasi dan sesudah disetujui, peserta, nilai, penugasan, dan jadwal sesi dikunci. Pengembalian untuk perbaikan membuka kembali pengisian dan pengiriman ulang.
- Persetujuan pusat mengubah status sesi menjadi selesai. Penerbitan/pengiriman sertifikat mengikuti alur yang sudah ada; sabuk diperbarui saat sertifikat dikirim.
- Setiap pengiriman serta keputusan menyimpan salinan data dan nilai beserta pelaku, waktu, dan catatan. Ujian lama yang sudah selesai tetap dapat menggunakan sertifikatnya.

Migrasi: `php artisan migrate`.
