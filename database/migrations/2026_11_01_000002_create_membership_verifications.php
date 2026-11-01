<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('member')->change();
            $table->string('verification_status', 20)->default('pending')->index();
        });
        Schema::create('membership_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_level', 20);
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_verifications');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('verification_status'));
    }
};
