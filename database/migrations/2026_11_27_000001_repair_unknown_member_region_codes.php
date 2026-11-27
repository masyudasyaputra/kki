<?php

use App\Models\User;
use App\Services\MemberNumbers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'member_number_override') || !Schema::hasTable('member_number_changes')) return;
        User::where('role', 'member')->where('member_number_manual', false)
            ->whereNotNull('province_id')->where('member_number_override', 'like', '%UNK-%')
            ->chunkById(100, function ($members) {
                foreach ($members as $member) app(MemberNumbers::class)->repairUnknownRegion($member);
            });
    }

    public function down(): void
    {
        // Corrected identifiers and their audit history are retained.
    }
};
