<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('public_directory_consent')->default(false);
        });
        Schema::create('privacy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('directory_consent');
            $table->string('policy_version', 40);
            $table->timestamp('created_at');
        });
        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);
            $table->text('details');
            $table->string('status', 30)->default('pending')->index();
            $table->text('response')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('privacy_events');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('public_directory_consent'));
    }
};
