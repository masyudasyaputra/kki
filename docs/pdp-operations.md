# Operasional pelindungan data pribadi PP KKI

Fitur ini mendukung pelaksanaan UU 27/2022, bukan sertifikasi kepatuhan hukum penuh.
Rujukan: https://jdih.komdigi.go.id/produk_hukum/view/id/832/t/undangundang%20nomor%2027%20tahun%202022

## Fitur dan batasnya

- `/kebijakan-privasi`: pemberitahuan publik dan kontak WhatsApp yang ditetapkan pengguna.
- `/privasi`: preferensi publikasi dan permintaan milik akun sendiri, tersedia bagi semua akun login termasuk yang nonaktif.
- `/admin/privasi`: antrean khusus Super Admin, jawaban wajib, pencatatan penanggung jawab dan waktu, penutupan final.
- Pendaftaran baru mencatat pilihan checkbox publikasi secara terpisah. Tidak dicentang berarti tidak dipublikasikan. Verifikasi dan status aktif tidak memberikan persetujuan publikasi.
- Sesuai instruksi pengelola, migrasi `2026_11_18_000003_enable_initial_member_directory.php` mengaktifkan publikasi data awal yang belum memiliki catatan pilihan. Sumbernya ditandai `initial-data-admin-2026-11-18`, bukan bukti persetujuan individual. Pilihan penolakan/penarikan yang sudah tercatat dipertahankan. Pengelola tetap perlu memastikan dasar publikasi data awal dan memberi pemberitahuan kepada anggota.
- Pilihan direktori dicatat dengan versi kebijakan dan waktu di `privacy_events`. Penarikan langsung mengubah filter direktori; jumlah agregat terverifikasi tidak berubah.
- Isi permintaan dan jawaban terenkripsi memakai APP_KEY. Cadangkan kunci secara aman; kehilangan kunci membuat isi tak dapat dibaca.
- Penghapusan, akses/salinan data lengkap, koreksi, dan pembatasan di sistem terkait masih dilaksanakan pengurus setelah pemeriksaan; perubahan status tiket tidak menjalankan tindakan tersebut secara otomatis.
- Publikasi tidak memuat foto. Persetujuan direktori tidak mengatur endpoint kartu/QR/sertifikat yang memiliki tujuan verifikasi tersendiri; lakukan audit terpisah untuk semua endpoint dan file sebelum menyatakan kepatuhan menyeluruh.

## Penanganan permintaan

1. Pantau antrean setiap hari dan kanal WhatsApp +62 817-6687-726. Permintaan dari WhatsApp juga harus dicatat dalam register operasional; formulir portal saat ini hanya untuk pemilik akun login.
2. Periksa identitas dan kewenangan secara proporsional. Jangan meminta KTP atau data berlebihan sebagai prosedur default. Untuk anak, dapatkan dan rekam kewenangan/persetujuan wali sebelum pemrosesan terkait; portal belum memverifikasi usia/wali secara otomatis.
3. Untuk akses/koreksi/penarikan/pembatasan, perhatikan kewajiban 3 × 24 jam pada Pasal 30, 32, 40, 41. Antrean menampilkan tenggat sejak permintaan portal diterima. Pastikan salinan data tidak mengungkap pihak lain.
4. Untuk penghapusan, inventarisasi profil, foto, dokumen, log, pembayaran, sertifikat, penyedia layanan dan cadangan; tetapkan dasar retensi atau hapus secara aman dan dokumentasikan alasannya. Jangan menghapus catatan yang wajib dipertahankan tanpa kajian.
5. Jalankan tindakan, lalu sampaikan hasil/alasan lewat tiket. Jangan menandai selesai hanya karena permintaan sudah dibaca. Status final tidak dapat ditimpa; permintaan lanjutan dicatat sebagai tiket baru.

## Penetapan yang masih dibutuhkan pengurus

- Identitas/alamat pengendali dan petugas penanggung jawab, serta pemeriksaan kewajiban penunjukan fungsi PDP.
- Inventaris pemrosesan: tujuan, dasar hukum per tujuan, jenis data, penerima, penyedia, lokasi penyimpanan/transfer lintas negara, kontrol akses, dan periode retensi konkret. Teks kebijakan perlu diperbarui setelah keputusan ditetapkan.
- Register persetujuan wali, jadwal retensi dan penghapusan cadangan; jangan menyamakan checkbox akun dengan pemeriksaan wali yang sah.
- SOP insiden, penilaian risiko/dampak, kontrak penyedia, kontrol hosting, pemulihan cadangan, tinjauan akses, dan pelatihan pengurus. Pasal 46 mengatur pemberitahuan tertulis paling lambat 3 × 24 jam dalam hal kegagalan pelindungan data, termasuk data terungkap, waktu/cara, dan upaya penanganan. Konsultasikan kewajiban aktual dengan penanggung jawab hukum.

## Penerapan

Jalankan migrasi `2026_11_18_000002_add_privacy_center.php` pada setiap lingkungan sebelum menerbitkan kode. Data lingkungan lokal berbeda dengan situs pada screenshot sebelumnya; migrasi lokal tidak memperbarui database hosting.
