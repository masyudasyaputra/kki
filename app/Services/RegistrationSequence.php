<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RegistrationSequence
{
    public function next(string $type, int $minimum = 0): int
    {
        return DB::transaction(function () use ($type, $minimum) {
            DB::table('registration_counters')->insertOrIgnore(['type' => $type, 'last_number' => $minimum]);
            $counter = DB::table('registration_counters')->where('type', $type)->lockForUpdate()->first();
            $next = max((int) $counter->last_number, $minimum) + 1;
            DB::table('registration_counters')->where('type', $type)->update(['last_number' => $next]);
            return $next;
        });
    }
}
