# Data dummy lokal KKI

Data dibuat khusus di MySQL **127.0.0.1:3306**, database **kki**, dengan `APP_ENV=local`.
Seluruh akun dan transaksi dummy ditandai `[DEMO]`, domain `@demo.kki.test`, atau nomor berawalan `DEMO-`.
Akun Super Admin dan data yang sudah ada tetap dipertahankan.

**Password awal seluruh akun demo: `DemoKKI!2026`**

| Pengujian | Akun |
| --- | --- |
| PB / akses nasional | `pb@demo.kki.test` |
| Pengprov Sumatera Utara | `pengprov.sumut@demo.kki.test` |
| Admin Pengprov | `admin-pengprov.sumut@demo.kki.test` |
| Pengcab Medan | `pengcab.medan@demo.kki.test` |
| Admin Pengcab | `admin-pengcab.medan@demo.kki.test` |
| Admin Dojo Medan 1 | `dojo.medan1@demo.kki.test` |
| Penguji Sumatera Utara | `penguji.sumut@demo.kki.test` |
| Verifikasi PB | `verifikator.pb@demo.kki.test` |
| Verifikasi Dewan Guru | `verifikator.dewan-guru@demo.kki.test` |
| Verifikasi Pengprov | `verifikator.sumut@demo.kki.test` |
| Verifikasi Pengcab | `verifikator.medan@demo.kki.test` |
| Anggota menunggu verifikasi | `anggota001@demo.kki.test` |
| Anggota dengan sertifikat KYU diterima | `anggota002@demo.kki.test` |
| Anggota perlu perbaikan | `anggota003@demo.kki.test` |
| Anggota dengan sertifikat DAN diterima | `anggota004@demo.kki.test` |
| Anggota sertifikat DAN siap dikirim | `anggota005@demo.kki.test` |
| Anggota penilaian sedang berlangsung | `anggota006@demo.kki.test` |
| Anggota sertifikat KYU siap dikirim | `anggota007@demo.kki.test` |
| Anggota pembayaran gagal | `anggota008@demo.kki.test` |

Daftar seluruh akun, role, dan nomor WhatsApp dummy tersedia di **`storage/app/demo/akun-uji.csv`**.
Alternatif tanpa mengetik akun: masuk sebagai admin, buka **Manajemen User**, lalu klik **Login as**.

## Cakupan data

- Tiga provinsi: Sumatera Utara, Jawa Barat, Jawa Timur; enam kabupaten/kota dan kecamatan terkait.
- Dua dojo per kota, dengan SK aktif, segera berakhir, dan kedaluwarsa; pengurus PB/Pengprov/Pengcab.
- Akun PB, Pengprov, admin Pengprov, Pengcab, admin Pengcab, admin dojo, penguji, dan seluruh level verifikator.
- 96 anggota dengan variasi tingkat sabuk, status aktif, pembayaran, dan verifikasi.
- Tarif iuran per provinsi dan biaya ujian untuk semua tingkat sabuk.
- 18 sesi ujian dummy: DAN/KYU terbuka, KYU berlangsung, DAN/KYU selesai, dan KYU draf.
- Peserta, penugasan penguji, nilai lengkap/belum lengkap, lulus, tidak lulus, dan percobaan.
- Pembayaran iuran dan ujian berstatus lunas, tertunda, dan gagal. Data pembayaran hanya simulasi lokal; tidak membuat transaksi atau tautan pembayaran DOKU.
- Empat template sertifikat: DAN/KYU desain bawaan dan DAN/KYU PDF dengan dua halaman dan koordinat teks.
- Sertifikat sudah dikirim, sertifikat terbit tetapi belum dikirim, serta peserta lulus yang belum diterbitkan sertifikatnya.
- Riwayat kenaikan sabuk dari pengiriman sertifikat, riwayat verifikasi, serta contoh catatan Login as.

## Skenario yang dapat dicoba

1. **Pembatasan wilayah:** bandingkan PB, `pengprov.sumut`, `pengprov.jabar`, dan `pengcab.medan`. Akun Jawa Timur memakai akhiran `jatim`; akun kota lainnya memakai `binjai`, `bandung`, `bogor`, `surabaya`, atau `malang`.
2. **Verifikasi:** pilih anggota001 untuk antrean baru, anggota003 untuk perbaikan, dan anggota002 untuk data disetujui.
3. **Pendaftaran ujian:** buka sesi `[DEMO] Pendaftaran KYU SUMUT` atau `[DEMO] Pendaftaran DAN SUMUT`.
4. **Penilaian:** masuk sebagai penguji dan buka `[DEMO] Penilaian KYU Berlangsung SUMUT`; lengkapi nilai yang kosong, lalu finalisasi sebagai admin.
5. **Sertifikat:** buka `[DEMO] Selesai KYU - Siap Kirim SUMUT` atau sesi DAN yang serupa, tab Pendaftaran. Coba kirim per peserta atau semua; peserta tidak lulus akan dilewati dengan alasan.
6. **Akun anggota:** buka anggota002/004 untuk melihat dan mengunduh sertifikat yang diterima. Setelah mengirim sertifikat anggota005/007, periksa perubahan sabuk dan riwayatnya.
7. **Editor PDF:** buka Pengaturan Master → Template Sertifikat → `[DEMO] DAN - PDF Koordinat` → Atur koordinat.
8. **Dashboard:** bandingkan rekap keuangan bulan berjalan, jumlah anggota, dan masa berlaku SK.

## Menjalankan kembali

```powershell
php artisan db:seed --class=LocalDemoSeeder
```

Seeder ini tidak mengosongkan tabel dan tidak mereset perubahan yang sudah dibuat pada data demo.
Data baru dengan identitas demo yang belum ada akan ditambahkan. Jangan gunakan `migrate:fresh`,
`db:wipe`, atau `DatabaseSeeder` untuk mengisi ulang skenario ini: seeder bawaan `UserTestingSeeder`
menghapus seluruh user.

Kredensial pada CSV adalah password **awal**; jika Anda mengganti password akun demo, seeder tidak mengembalikannya.

## Hasil pengisian awal

141 akun demo (96 anggota), 12 dojo, 11 pengurus, 18 sesi ujian, 120 pendaftaran ujian,
60 penilaian, 168 pembayaran, 60 riwayat verifikasi, 4 template sertifikat demo,
24 sertifikat (18 terkirim dan 6 belum dikirim), serta 18 riwayat kenaikan sabuk.

Status anggota: 36 menunggu verifikasi, 48 disetujui, 12 perlu perbaikan.
Status pembayaran: 108 lunas, 48 tertunda, 12 gagal.

Pemeriksaan awal mencakup 28 halaman aplikasi dan semua berhasil dimuat.
Menjalankan ulang seeder tidak menambah duplikasi dan tidak mengubah akun asli.
Contoh PDF dua halaman yang sudah diisi dari data demo:

- `storage/app/demo/contoh-sertifikat-dan.pdf`
- `storage/app/demo/contoh-sertifikat-kyu.pdf`

Ringkasan hasil pemeriksaan tersimpan di `storage/app/demo/verification-summary.json`
dan `storage/app/demo/page-checks.json`.
