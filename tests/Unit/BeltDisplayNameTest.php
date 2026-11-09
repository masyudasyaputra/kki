<?php

namespace Tests\Unit;

use App\Models\BeltLevel;
use PHPUnit\Framework\TestCase;

class BeltDisplayNameTest extends TestCase
{
    public function test_display_name_includes_the_exact_kyu_or_dan_rank(): void
    {
        foreach ([['Hitam', 'DAN I', 'Hitam (DAN I)'], ['Hitam', 'DAN II', 'Hitam (DAN II)'], ['Putih', 'Kyu 7', 'Putih (Kyu 7)']] as [$name, $rank, $expected]) {
            $this->assertSame($expected, (new BeltLevel(['name' => $name, 'kyu_dan' => $rank]))->display_name);
        }
    }

    public function test_missing_rank_is_not_invented(): void
    {
        $this->assertSame('Hitam', (new BeltLevel(['name' => 'Hitam']))->display_name);
    }
}
