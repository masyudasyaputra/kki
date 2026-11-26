<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_counters', function (Blueprint $table) {
            $table->string('type')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::table('officials', fn (Blueprint $table) => $table->unsignedBigInteger('registration_sequence')->nullable()->unique());
        DB::transaction(function () {
            $last = 0;
            \App\Models\Official::orderBy('id')->each(function ($official) use (&$last) {
                $last = max($last + 1, (int) ($official->user_id ?: $official->id));
                $official->forceFill(['registration_sequence' => $last]);
                if (!$official->official_number_manual) {
                    do {
                        $official->registration_sequence = $last;
                        $number = app(\App\Services\OfficialNumbers::class)->automatic($official);
                        $collision = \App\Models\Official::where('official_number', $number)->whereKeyNot($official->id)->exists();
                        if ($collision) $last++;
                    } while ($collision);
                    $official->official_number = $number;
                }
                $official->saveQuietly();
            });
            DB::table('registration_counters')->insert(['type' => 'official', 'last_number' => $last]);
        });
    }

    public function down(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->dropUnique(['registration_sequence']);
            $table->dropColumn('registration_sequence');
        });
        Schema::dropIfExists('registration_counters');
    }
};
