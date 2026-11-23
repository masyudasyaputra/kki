<?php

namespace Tests\Unit;

use App\Models\Dojo;
use PHPUnit\Framework\TestCase;

class DojoRegistrationNumberTest extends TestCase
{
    public function test_registration_number_is_automatic_unique_and_stable(): void
    {
        $dojo = new Dojo(['name' => 'Dojo Lama']);
        $dojo->id = 1;
        $this->assertSame('KKI-DOJO-000001', $dojo->registration_number);

        $dojo->fill(['name' => 'Dojo Baru', 'city_id' => 2]);
        $this->assertSame('KKI-DOJO-000001', $dojo->registration_number);

        $other = new Dojo;
        $other->id = 1000000;
        $this->assertSame('KKI-DOJO-1000000', $other->registration_number);
        $this->assertNotSame($dojo->registration_number, $other->registration_number);
    }

    public function test_unsaved_dojo_has_no_registration_number(): void
    {
        $this->assertNull((new Dojo)->registration_number);
    }
}
