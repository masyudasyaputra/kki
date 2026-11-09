<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamFeeSeeder extends Seeder
{
    /**
     * Jalankan seeder berdasarkan tabel exam_fees.
     */
    public function run(): void
    {
        $amounts = ['Kyu 6' => 150000, 'Kyu 5' => 190000, 'Kyu 4' => 210000,
            'Kyu 3' => 230000, 'Kyu 2' => 250000, 'Kyu 1' => 250000];
        $fees = \App\Models\BeltLevel::active()->whereIn('kyu_dan', array_keys($amounts))->get()
            ->map(fn ($belt) => ['belt_level_id' => $belt->id, 'amount' => $amounts[$belt->kyu_dan]]);

        foreach ($fees as $fee) {
            DB::table('exam_fees')->updateOrInsert(
                ['belt_level_id' => $fee['belt_level_id']],
                [
                    'amount' => $fee['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
