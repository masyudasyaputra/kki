<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_impersonations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name'); $table->string('target_name');
            $table->timestamp('started_at'); $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_impersonations');
    }
};
