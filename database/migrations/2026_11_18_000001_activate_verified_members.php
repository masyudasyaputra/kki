<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'member')
            ->where('verification_status', 'approved')
            ->where('is_active', false)
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        // Activation cannot be reversed without overwriting subsequent admin decisions.
    }
};
