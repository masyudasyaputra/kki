<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('requested_belt_level_id')->nullable()->constrained('belt_levels')->nullOnDelete();
            $t->string('belt_diploma_path')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['requested_belt_level_id']);
            $t->dropColumn(['requested_belt_level_id', 'belt_diploma_path']);
        });
    }
};
