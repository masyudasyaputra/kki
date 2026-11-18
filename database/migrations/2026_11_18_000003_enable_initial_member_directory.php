<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Initial import instruction, not evidence of individual consent.
            // Preserve every explicit choice already recorded by an account.
            \App\Models\User::members()->where('public_directory_consent', false)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('privacy_events')->whereColumn('privacy_events.user_id', 'users.id'))
                ->orderBy('id')->chunkById(100, function ($members) {
                    foreach ($members as $member) {
                        DB::table('users')->where('id', $member->id)->update(['public_directory_consent' => true]);
                        DB::table('privacy_events')->insert(['user_id' => $member->id, 'directory_consent' => true,
                            'policy_version' => 'initial-data-admin-2026-11-18', 'created_at' => now()]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Do not overwrite choices made after this initial-data update.
    }
};
