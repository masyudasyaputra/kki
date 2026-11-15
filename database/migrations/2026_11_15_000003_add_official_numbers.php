<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $t) {
            $t->string('official_number', 40)->nullable()->unique();
            $t->boolean('official_number_manual')->default(false);
        });
        \App\Models\Official::orderBy('id')->chunkById(100, function ($officials) {
            foreach ($officials as $official) app(\App\Services\OfficialNumbers::class)->issue($official);
        });
    }

    public function down(): void
    {
        Schema::table('officials', function (Blueprint $t) {
            $t->dropUnique(['official_number']);
            $t->dropColumn(['official_number', 'official_number_manual']);
        });
    }
};
