<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_type', 3)->default('kyu');
            $table->json('certificate_settings')->nullable();
        });
        DB::table('exams')->where('name', 'like', '%DAN%')->update(['exam_type' => 'dan']);
        Schema::table('exam_scores', function (Blueprint $table) {
            foreach (['kihon', 'kata', 'kumite'] as $field) $table->string($field, 30)->nullable()->default(null)->change();
            foreach (['theory', 'paper', 'physical', 'self_defense', 'ippon_kumite'] as $field) $table->decimal($field, 5, 2)->nullable();
        });
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); $table->string('exam_type', 3);
            $table->json('settings'); $table->timestamps();
        });
        Schema::create('exam_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique(); $table->unsignedInteger('revision')->default(1);
            $table->json('snapshot'); $table->json('previous_versions')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_certificates'); Schema::dropIfExists('certificate_templates');
        Schema::table('exam_scores', fn (Blueprint $table) => $table->dropColumn(['theory', 'paper', 'physical', 'self_defense', 'ippon_kumite']));
        Schema::table('exams', fn (Blueprint $table) => $table->dropColumn(['exam_type', 'certificate_settings']));
    }
};
