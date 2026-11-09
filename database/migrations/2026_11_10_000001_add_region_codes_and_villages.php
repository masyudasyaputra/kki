<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['provinces', 'cities', 'districts'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('code', 20)->nullable()->unique();
                });
            }
        }

        if (!Schema::hasTable('villages')) {
            Schema::create('villages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('district_id')->constrained()->restrictOnDelete();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->timestamps();
                $table->index(['district_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        foreach (['districts', 'cities', 'provinces'] as $tableName) {
            if (Schema::hasColumn($tableName, 'code')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropUnique($tableName.'_code_unique');
                    $table->dropColumn('code');
                });
            }
        }
    }
};
