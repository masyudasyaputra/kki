<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class IndonesiaRegionSeeder extends Seeder
{
    private const DATA_DIRECTORY = 'data/regions';
    private const BATCH_SIZE = 500;

    public function run(): void
    {
        $provinceByCode = [];
        $provinceByName = [];
        foreach (Province::query()->get() as $province) {
            if ($province->code) $provinceByCode[$province->code] = $province;
            $provinceByName[$this->normalize($province->name)] = $province;
        }

        $provinceCount = 0;
        foreach ($this->readCsv('provinsi.csv') as $row) {
            $provinceCount++;
            $code = $row['id'];
            $province = $provinceByCode[$code] ?? $provinceByName[$this->normalize($row['name'])] ?? new Province();
            $province->code = $code;
            $province->name = mb_strtoupper($row['name'], 'UTF-8');
            $province->save();
            $provinceByCode[$code] = $province;
            $provinceByName[$this->normalize($row['name'])] = $province;
        }

        $provinceIdByCode = collect($provinceByCode)->mapWithKeys(fn (Province $province, $code) => [(string) $code => $province->id])->all();
        $provinceCodeById = collect($provinceByCode)->mapWithKeys(fn (Province $province, $code) => [(string) $province->id => (string) $code])->all();
        $cityByCode = [];
        $cityByName = [];
        foreach (City::query()->get() as $city) {
            if ($city->code) $cityByCode[$city->code] = $city;
            $provinceCode = $provinceCodeById[(string) $city->province_id] ?? '';
            $cityByName[$city->province_id.'|'.$this->normalizeCity($city->name, $provinceCode)] = $city;
        }

        $cityCount = 0;
        foreach ($this->readCsv('kabupaten_kota.csv') as $row) {
            $cityCount++;
            $provinceCode = explode('.', $row['id'], 2)[0];
            $provinceId = $provinceIdByCode[$provinceCode] ?? null;
            if (!$provinceId) throw new RuntimeException("Provinsi {$provinceCode} tidak ditemukan untuk kota {$row['id']}.");

            $key = $provinceId.'|'.$this->normalizeCity($row['name'], $provinceCode);
            $city = $cityByCode[$row['id']] ?? $cityByName[$key] ?? new City();
            $city->province_id = $provinceId;
            $city->code = $row['id'];
            $city->name = mb_strtoupper($row['name'], 'UTF-8');
            $city->save();
            $cityByCode[$row['id']] = $city;
            $cityByName[$key] = $city;
        }

        $cityIdByCode = collect($cityByCode)->mapWithKeys(fn (City $city, $code) => [(string) $code => $city->id])->all();
        $districtByName = [];
        $districtByCode = [];
        foreach (District::query()->get() as $district) {
            $districtByName[$district->city_id.'|'.$this->normalize($district->name)] = $district;
            if ($district->code) $districtByCode[$district->code] = $district;
        }

        $newDistrictRows = [];
        $districtCodes = [];
        $districtCount = 0;
        foreach ($this->readCsv('kecamatan.csv') as $row) {
            $districtCount++;
            $cityCode = substr($row['id'], 0, 5);
            $cityId = $cityIdByCode[$cityCode] ?? null;
            if (!$cityId) throw new RuntimeException("Kabupaten/kota {$cityCode} tidak ditemukan untuk kecamatan {$row['id']}.");

            $key = $cityId.'|'.$this->normalize($row['name']);
            if (isset($districtByCode[$row['id']])) {
                $district = $districtByCode[$row['id']];
                DB::table('districts')->where('id', $district->id)->update([
                    'city_id' => $cityId, 'name' => mb_strtoupper($row['name'], 'UTF-8'), 'updated_at' => now(),
                ]);
                $districtCodes[$row['id']] = $district->id;
                continue;
            }

            $existingDistrict = $districtByName[$key] ?? null;
            if ($existingDistrict && $existingDistrict->code && $existingDistrict->code !== $row['id']) {
                $existingDistrict = null;
            }
            if ($existingDistrict) {
                DB::table('districts')->where('id', $existingDistrict->id)->update([
                    'code' => $row['id'], 'city_id' => $cityId,
                    'name' => mb_strtoupper($row['name'], 'UTF-8'), 'updated_at' => now(),
                ]);
                $districtCodes[$row['id']] = $existingDistrict->id;
                $districtByCode[$row['id']] = $existingDistrict;
                continue;
            }

            $newDistrictRows[] = [
                'city_id' => $cityId,
                'code' => $row['id'],
                'name' => mb_strtoupper($row['name'], 'UTF-8'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (count($newDistrictRows) >= self::BATCH_SIZE) {
                DB::table('districts')->insertOrIgnore($newDistrictRows);
                $newDistrictRows = [];
            }
        }
        if ($newDistrictRows) DB::table('districts')->insertOrIgnore($newDistrictRows);

        $districtIdByCode = DB::table('districts')->whereNotNull('code')->pluck('id', 'code')->all();
        $districtIdByCode = array_merge($districtIdByCode, $districtCodes);

        $villageBatch = [];
        $villageCount = 0;
        foreach ($this->readCsv('kelurahan.csv') as $row) {
            $districtCode = substr($row['id'], 0, 8);
            $districtId = $districtIdByCode[$districtCode] ?? null;
            if (!$districtId) throw new RuntimeException("Kecamatan {$districtCode} tidak ditemukan untuk kelurahan {$row['id']}.");

            $villageBatch[] = [
                'district_id' => $districtId,
                'code' => $row['id'],
                'name' => mb_strtoupper($row['name'], 'UTF-8'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (count($villageBatch) >= self::BATCH_SIZE) {
                DB::table('villages')->upsert($villageBatch, ['code'], ['district_id', 'name', 'updated_at']);
                $villageCount += count($villageBatch);
                $villageBatch = [];
            }
        }
        if ($villageBatch) {
            DB::table('villages')->upsert($villageBatch, ['code'], ['district_id', 'name', 'updated_at']);
            $villageCount += count($villageBatch);
        }

        $removedLegacyCities = $this->removeUnusedLegacySampleCities();

        $this->command?->info(sprintf(
            'Data wilayah tersinkron: %s provinsi, %s kabupaten/kota, %s kecamatan, %s kelurahan.%s',
            number_format($provinceCount), number_format($cityCount), number_format($districtCount), number_format($villageCount),
            $removedLegacyCities ? ' '.$removedLegacyCities.' baris contoh lama yang tidak dipakai dibersihkan.' : ''
        ));
    }

    /** @return \Generator<int, array{id: string, name: string}> */
    private function readCsv(string $filename): \Generator
    {
        $path = database_path(self::DATA_DIRECTORY.'/'.$filename);
        if (!is_file($path)) throw new RuntimeException("File data wilayah tidak ditemukan: {$path}");

        $file = new SplFileObject($path, 'r');
        $file->setCsvControl(',', '"', '');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $isHeader = true;
        foreach ($file as $columns) {
            if (!$columns || count($columns) < 2) continue;
            if ($isHeader) {
                $isHeader = false;
                continue;
            }
            $code = trim((string) ($columns[0] ?? ''), "\xEF\xBB\xBF \t\n\r\0\x0B\"");
            $name = trim((string) ($columns[1] ?? ''), " \t\n\r\0\x0B\"");
            if ($code === '' || $name === '') continue;
            yield ['id' => $code, 'name' => $name];
        }
    }

    private function normalize(string $name): string
    {
        $name = mb_strtoupper(trim($name), 'UTF-8');
        $name = preg_replace('/\s*\([^)]*\)/u', '', $name);
        $name = preg_replace('/^(PROVINSI|PROV\.?|KABUPATEN|KAB\.?|KOTA|KECAMATAN|KEC\.?)\s+/u', '', $name);

        return preg_replace('/[^\pL\pN]+/u', '', $name) ?? '';
    }

    private function normalizeCity(string $name, string $provinceCode): string
    {
        $name = mb_strtoupper(trim($name), 'UTF-8');
        $name = preg_replace('/\s*\([^)]*\)/u', '', $name);
        $name = preg_replace('/^(KABUPATEN|KAB\.?)\s+/u', '', $name);
        if ($provinceCode === '31') $name = preg_replace('/^KOTA\s+/u', '', $name);

        return preg_replace('/[^\pL\pN]+/u', '', $name) ?? '';
    }

    private function removeUnusedLegacySampleCities(): int
    {
        $provinceCodes = Province::query()->whereNotNull('code')->pluck('code', 'id')->all();
        $officialNames = [];
        foreach (City::query()->whereNotNull('code')->get(['province_id', 'name']) as $city) {
            $provinceCode = (string) ($provinceCodes[$city->province_id] ?? '');
            $officialNames[$this->normalizeCity($city->name, $provinceCode)] = true;
        }

        $removed = 0;
        foreach (City::query()->whereNull('code')->get() as $city) {
            $provinceCode = (string) ($provinceCodes[$city->province_id] ?? '');
            $key = $this->normalizeCity($city->name, $provinceCode);
            $isDuplicate = isset($officialNames[$key]);
            $isOldSample = mb_strtoupper(trim($city->name), 'UTF-8') === 'KAB. LIMBOTO';
            if (!$isDuplicate && !$isOldSample) continue;

            $hasReferences = DB::table('users')->where('city_id', $city->id)->exists()
                || DB::table('dojos')->where('city_id', $city->id)->exists()
                || DB::table('officials')->where('city_id', $city->id)->exists()
                || DB::table('districts')->where('city_id', $city->id)->exists();
            if (!$hasReferences) {
                $city->delete();
                $removed++;
            }
        }

        return $removed;
    }
}
