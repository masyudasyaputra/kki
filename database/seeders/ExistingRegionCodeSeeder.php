<?php

namespace Database\Seeders;

use App\Models\{City, Province};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class ExistingRegionCodeSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [];
        foreach ($this->rows('provinsi.csv') as [$code, $name]) $provinces[$this->normalize($name)] = $code;
        $cities = [];
        foreach ($this->rows('kabupaten_kota.csv') as [$code, $name]) {
            $provinceCode = substr($code, 0, 2);
            $cities[$provinceCode][$this->normalizeCity($name, $provinceCode)][] = $code;
        }
        $updated = 0;
        $unmatched = [];
        DB::transaction(function () use ($provinces, $cities, &$updated, &$unmatched) {
            foreach (Province::all() as $province) {
                $provinceCode = $provinces[$this->normalize($province->name)] ?? null;
                if (!$provinceCode) continue;
                if (!$province->code) $province->update(['code' => $provinceCode]);
                // Never reconcile a conflicting province code by guessing.
                if ((string) $province->code !== (string) $provinceCode) continue;
                foreach (City::where('province_id', $province->id)->get() as $city) {
                    $matches = $cities[$provinceCode][$this->normalizeCity($city->name, $provinceCode)] ?? [];
                    if (count($matches) !== 1 || ($city->code && $city->code !== $matches[0])) {
                        $unmatched[] = $province->name.' / '.$city->name;
                        continue;
                    }
                    if (!$city->code) {
                        $city->update(['code' => $matches[0]]);
                        $updated++;
                    }
                }
            }
        });
        $this->command?->info("Kode kabupaten/kota diisi: {$updated}. Tidak cocok/konflik: ".count($unmatched));
        foreach ($unmatched as $name) $this->command?->warn($name);
    }

    private function rows(string $filename): \Generator
    {
        $file = new SplFileObject(database_path('data/regions/'.$filename));
        $file->setCsvControl(',', '"', '');
        while (!$file->eof()) {
            $row = $file->fgetcsv();
            if (!is_array($row) || !preg_match('/^\d{2}(?:\.\d{2})?$/D', (string) ($row[0] ?? ''))) continue;
            yield [(string) $row[0], (string) $row[1]];
        }
    }

    private function normalize(string $name): string
    {
        $name = mb_strtoupper(trim($name));
        $name = preg_replace('/\([^)]*\)/u', '', $name);
        $name = preg_replace('/^(PROVINSI|PROV\.?)\s+/u', '', $name);
        $name = preg_replace('/[^\pL\pN]/u', '', $name);
        return ['JAKARTA' => 'DKIJAKARTA', 'DIYOGYAKARTA' => 'DAERAHISTIMEWAYOGYAKARTA', 'YOGYAKARTA' => 'DAERAHISTIMEWAYOGYAKARTA'][$name] ?? $name;
    }

    private function normalizeCity(string $name, string $provinceCode): string
    {
        $name = mb_strtoupper(trim($name));
        $name = preg_replace('/^(KABUPATEN|KAB\.?)\s+/u', '', $name);
        if ($provinceCode === '31') $name = preg_replace('/^KOTA\s+/u', '', $name);
        return preg_replace('/[^\pL\pN]/u', '', $name);
    }
}
