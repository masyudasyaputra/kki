<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\{User, MemberNumberChange};

return new class extends Migration {
    public function up(): void
    {
        User::where('member_number_override', 'like', '%MSH%')->with('beltLevel')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                if ($user->isBlackBelt() || !preg_match('/(^|-)MSH(-|$)/i', $user->member_number_override)) continue;
                DB::transaction(function () use ($user) {
                    $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                    if ($locked->isBlackBelt()) return;
                    $before = $locked->member_number_override;
                    $number = preg_replace('/(^|-)MSH-?/i', '$1', $before);
                    if (strlen($number) < 3 || User::where('member_number_override', $number)->whereKeyNot($locked->id)->exists()) {
                        $base = $locked->automatic_member_number;
                        $split = strrpos($base, '-');
                        $prefix = substr($base, 0, $split + 1);
                        $sequence = (int) substr($base, $split + 1);
                        do { $number = $prefix.str_pad((string) $sequence++, 6, '0', STR_PAD_LEFT); }
                        while (User::where('member_number_override', $number)->whereKeyNot($locked->id)->exists());
                    }
                    $locked->forceFill(['member_number_override' => $number])->saveQuietly();
                    MemberNumberChange::create(['member_id' => $locked->id, 'actor_id' => null, 'before_number' => $before, 'after_number' => $number,
                        'mode' => 'auto', 'reason' => 'Penyesuaian kode MSH: hanya untuk anggota sabuk hitam.']);
                });
            }
        });
    }

    public function down(): void
    {
        // Corrections preserve their audit history and are not reversed automatically.
    }
};
