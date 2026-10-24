<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BeltLevelSeeder::class,
            IndonesiaRegionSeeder::class,
            FeeStartingSeeder::class,
            ExamFeeSeeder::class,
            UserTestingSeeder::class,
        ]);
    }
}
