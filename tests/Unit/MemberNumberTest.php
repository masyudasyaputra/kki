<?php

namespace Tests\Unit;

use App\Models\Province;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class MemberNumberTest extends TestCase
{
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
