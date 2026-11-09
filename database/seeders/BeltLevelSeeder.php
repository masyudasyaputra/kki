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

        foreach (\App\Services\BeltCatalog::defaults() as $belt) {
            BeltLevel::updateOrCreate(['name' => $belt['name'], 'kyu_dan' => $belt['kyu_dan']], $belt + ['is_active' => true]);
        }
    }
}
