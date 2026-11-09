# Indonesia administrative regions

Source: [alifbint/indonesia-38-provinsi](https://github.com/alifbint/indonesia-38-provinsi), branch `main`.
Downloaded on 2026-11-10. CSV records contain Kemendagri-style region codes and names for provinces, regencies/cities, districts, and villages.

Run `php artisan db:seed --class=IndonesiaRegionSeeder` after migrations to synchronize the CSV files into the application database. Existing region IDs are retained when names match; extra manually entered districts are kept.
