<?php

namespace Database\Seeders;

use App\Models\BeltLevel;
use Illuminate\Database\Seeder;

class BeltLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (BeltLevel::count() > 0) {
            return;
        }

        $belts = [
            ['id' => 1,  'name' => 'Putih',       'kyu_dan' => 'Kyu 10', 'order' => 1,  'membership_fee' => 50000],
            ['id' => 2,  'name' => 'Kuning Muda', 'kyu_dan' => 'Kyu 9',  'order' => 2,  'membership_fee' => 60000],
            ['id' => 3,  'name' => 'Kuning Tua',  'kyu_dan' => 'Kyu 8',  'order' => 3,  'membership_fee' => 65000],
            ['id' => 4,  'name' => 'Orange',      'kyu_dan' => 'Kyu 7',  'order' => 4,  'membership_fee' => 70000],
            ['id' => 5,  'name' => 'Hijau',       'kyu_dan' => 'Kyu 6',  'order' => 5,  'membership_fee' => 75000],
            ['id' => 6,  'name' => 'Biru',        'kyu_dan' => 'Kyu 5',  'order' => 6,  'membership_fee' => 90000],
            ['id' => 7,  'name' => 'Ungu',        'kyu_dan' => 'Kyu 4',  'order' => 7,  'membership_fee' => 100000],
            ['id' => 8,  'name' => 'Cokelat',     'kyu_dan' => 'Kyu 3',  'order' => 8,  'membership_fee' => 110000],
            ['id' => 9,  'name' => 'Cokelat',     'kyu_dan' => 'Kyu 2',  'order' => 9,  'membership_fee' => 110000],
            ['id' => 10, 'name' => 'Cokelat',     'kyu_dan' => 'Kyu 1',  'order' => 10, 'membership_fee' => 110000],
            ['id' => 11, 'name' => 'Hitam',       'kyu_dan' => 'DAN I',  'order' => 11, 'membership_fee' => 150000],
            ['id' => 12, 'name' => 'Hitam',       'kyu_dan' => 'DAN II', 'order' => 12, 'membership_fee' => 150000],
            ['id' => 13, 'name' => 'Hitam',       'kyu_dan' => 'DAN III','order' => 13, 'membership_fee' => 200000],
            ['id' => 14, 'name' => 'Hitam',       'kyu_dan' => 'DAN IV', 'order' => 14, 'membership_fee' => 250000],
            ['id' => 15, 'name' => 'Hitam',       'kyu_dan' => 'DAN V',  'order' => 15, 'membership_fee' => 300000],
            ['id' => 16, 'name' => 'Hitam',       'kyu_dan' => 'DAN VI', 'order' => 16, 'membership_fee' => 500000],
        ];

        foreach ($belts as $belt) {
            BeltLevel::updateOrCreate(['id' => $belt['id']], $belt);
        }
    }
}
