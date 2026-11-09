<?php

namespace Tests\Unit;

use App\Models\Province;
use App\Models\BeltLevel;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class MemberNumberTest extends TestCase
{
    public function test_black_belt_member_number_has_msh_prefix(): void
    {
        foreach (['Hitam', 'HITAM', 'Sabuk Hitam'] as $name) {
            $user = new User;
            $user->id = 2;
            $user->setRelation('province', new Province(['name' => 'Sumatera Utara']));
            $user->setRelation('beltLevel', new BeltLevel(['name' => $name, 'kyu_dan' => 'DAN I']));
            $this->assertSame('MSH-SMT-000002', $user->member_number);
        }
    }

    public function test_kyu_member_number_starts_with_region_code(): void
    {
        $user = new User;
        $user->id = 2;
        $user->setRelation('province', new Province(['name' => 'Sumatera Utara']));
        $user->setRelation('beltLevel', new BeltLevel(['name' => 'Coklat', 'kyu_dan' => 'Kyu 1']));
        $this->assertSame('SMT-000002', $user->member_number);
    }
    public function test_member_number_uses_province_prefix_and_six_digits(): void
    {
        foreach (['Sumatera Utara' => 'SMT', 'SUMUT' => 'SMT', 'Jawa Barat' => 'JBR'] as $name => $prefix) {
            $user = new User;
            $user->id = 1;
            $user->setRelation('province', new Province(['name' => $name]));

            $this->assertSame($prefix . '-000001', $user->member_number);
        }
    }

    public function test_member_without_province_has_a_fallback_prefix(): void
    {
        $user = new User;
        $user->id = 123456;
        $user->setRelation('province', null);

        $this->assertSame('UNK-123456', $user->member_number);
    }
}
