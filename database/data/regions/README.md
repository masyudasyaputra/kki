# Indonesia administrative regions

Source: [alifbint/indonesia-38-provinsi](https://github.com/alifbint/indonesia-38-provinsi), branch `main`.
Downloaded on 2026-11-10. CSV records contain Kemendagri-style region codes and names for provinces, regencies/cities, districts, and villages.

Run `php artisan db:seed --class=IndonesiaRegionSeeder` after migrations to synchronize the CSV files into the application database. Existing region IDs are retained when names match; extra manually entered districts are kept.

Administrative-code reference: [Kepmendagri 300.2.2-2138 Tahun 2025](https://peraturan.bpk.go.id/Details/322942/keputusan-mendagri-no-nomor-30022-2138-tahun), amended by [Kepmendagri 300.2.2-2430 Tahun 2025](https://peraturan.bpk.go.id/Details/322912/keputusan-mendagri-no-30022-2430-tahun-2025). The CSV files above are a third-party transcription, not a download directly from Kemendagri. Example cross-check: Kota Denpasar `51.71` in the [Bali provincial government's administrative table](https://tarubali.baliprov.go.id/wilayah-administrasi-provinsi-bali/).

To fill codes on existing provinces/cities without importing or renaming regions, run `php artisan db:seed --class=ExistingRegionCodeSeeder`. It matches city names within the province, preserves the distinction between kabupaten and kota, and reports conflicting/unmatched records. Registration codes remove the dot (`51.71` → `5171`). Missing/invalid codes return `UNK`; database row IDs are never used as administrative codes.
