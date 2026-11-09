<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->json('material_scores')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropColumn('material_scores');
        });
    }
};
