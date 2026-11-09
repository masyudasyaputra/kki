# Master sabuk KYU

| Sabuk | Tingkat |
| --- | --- |
| Putih | Kyu 7 |
| Kuning | Kyu 6 |
| Hijau | Kyu 5 |
| Biru Strip | Kyu 4 |
| Biru Tua | Kyu 3 |
| Coklat Strip | Kyu 2 |
| Coklat Polos/Tua | Kyu 1 |

DAN I–VI dipertahankan. Pilihan pendaftaran, penilaian, konfigurasi biaya, dan filter menggunakan master aktif. Tingkat berikutnya ditentukan oleh urutan serta jenis ujian, tanpa bergantung pada ID database.

Migrasi `2026_11_09_000003_set_seven_kyu_belt_levels` mengonversi sabuk anggota lama dengan padanan:

- Putih → Putih.
- Kuning Muda, Kuning Tua, Orange → Kuning.
- Hijau → Hijau.
- Biru → Biru Strip.
- Ungu → Biru Tua.
- Cokelat Kyu 3 / Kyu 2 → Coklat Strip.
- Cokelat Kyu 1 → Coklat Polos/Tua.

Master lama diarsipkan dan tetap tersedia untuk relasi historis. Data ujian, pembayaran, riwayat sabuk, dan salinan sertifikat lama tidak ditulis ulang. Pengiriman sertifikat lama menggunakan padanan aktif saat memperbarui sabuk anggota.

Aturan biaya baru disalin dari tingkat perwakilan lama: Putih Kyu 10, Kuning Muda Kyu 9, Hijau Kyu 6, Biru Kyu 5, Ungu Kyu 4, Cokelat Kyu 2, dan Cokelat Kyu 1. Nilai pembayaran yang sudah tersimpan tetap dipertahankan.
