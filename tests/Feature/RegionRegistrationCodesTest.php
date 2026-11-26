<?php

namespace Tests\Feature;

use App\Models\{City, Province};
use Database\Seeders\ExistingRegionCodeSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegionRegistrationCodesTest extends TestCase
{
    public function test_sync_matches_official_codes_without_changing_ids_or_guessing_regions(): void
    {
        config(['database.default' => 'region_codes', 'database.connections.region_codes' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('provinces', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('code')->nullable()->unique(); $t->timestamps(); });
        Schema::create('cities', function (Blueprint $t) { $t->id(); $t->integer('province_id'); $t->string('name'); $t->string('code')->nullable()->unique(); $t->timestamps(); });
        $bali = Province::create(['name' => 'Bali']);
        $bandung = Province::create(['name' => 'Jawa Barat']);
        $denpasar = City::create(['province_id' => $bali->id, 'name' => 'KOTA DENPASAR']);
        $kab = City::create(['province_id' => $bandung->id, 'name' => 'KAB. BANDUNG']);
        $kota = City::create(['province_id' => $bandung->id, 'name' => 'KOTA BANDUNG']);
        $unknown = City::create(['province_id' => $bali->id, 'name' => 'Wilayah belum dipetakan']);
        $this->seed(ExistingRegionCodeSeeder::class);
        $this->assertSame('51.71', $denpasar->fresh()->code);
        $this->assertSame('5171', $denpasar->fresh()->registration_code);
        $this->assertSame('32.04', $kab->fresh()->code);
        $this->assertSame('32.73', $kota->fresh()->code);
        $this->assertNull($unknown->fresh()->code);
        $this->assertSame('UNK', $unknown->fresh()->registration_code);
        $this->assertSame($denpasar->id, City::where('code', '51.71')->firstOrFail()->id);
        $this->seed(ExistingRegionCodeSeeder::class);
        $this->assertDatabaseCount('cities', 4);
    }
}
