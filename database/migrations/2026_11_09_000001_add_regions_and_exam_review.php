<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->unique(['city_id', 'name']);
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_coach')->nullable();
        });
        foreach (\Illuminate\Support\Facades\DB::table('users')->whereNotNull('city_id')->whereNotNull('district')->select('city_id', 'district')->distinct()->get() as $row) {
            $name = trim($row->district);
            if ($name !== '') \Illuminate\Support\Facades\DB::table('districts')->insertOrIgnore(['city_id' => $row->city_id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('exams', function (Blueprint $table) {
            $table->string('review_status')->default('draft');
        });
        Schema::create('exam_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_reviews');
        Schema::table('exams', fn (Blueprint $table) => $table->dropColumn('review_status'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('first_coach'));
        Schema::dropIfExists('districts');
    }
};
