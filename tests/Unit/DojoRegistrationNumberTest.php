<?php

namespace Tests\Unit;

use App\Models\Dojo;
use App\Models\City;
use App\Models\Province;
use PHPUnit\Framework\TestCase;

class DojoRegistrationNumberTest extends TestCase
{
    public function test_registration_number_is_automatic_unique_and_stable(): void
    {
        $dojo = new Dojo(['name' => 'Dojo Lama']);
        $dojo->id = 1;
        $city = new City;
        $city->setRelation('province', new Province(['name' => 'Sumatera Utara']));
        $dojo->setRelation('city', $city);
        $this->assertSame('KKI-SMT-DOJO-00001', $dojo->registration_number);

        $dojo->fill(['name' => 'Dojo Baru', 'city_id' => 2]);
        $this->assertSame('KKI-SMT-DOJO-00001', $dojo->registration_number);

        $other = new Dojo;
        $other->id = 1000000;
        $other->setRelation('city', null);
        $other->setRelation('province', new Province(['name' => 'Jawa Barat']));
        $this->assertSame('KKI-JBR-DOJO-1000000', $other->registration_number);
        $this->assertNotSame($dojo->registration_number, $other->registration_number);
    }

    public function test_unsaved_dojo_has_no_registration_number(): void
    {
        $this->assertNull((new Dojo)->registration_number);
    }

    public function test_dojo_without_region_uses_fallback_code(): void
    {
        $dojo = new Dojo;
        $dojo->id = 12;
        $dojo->setRelation('city', null);
        $dojo->setRelation('province', null);
        $this->assertSame('KKI-UNK-DOJO-00012', $dojo->registration_number);
    }
}
