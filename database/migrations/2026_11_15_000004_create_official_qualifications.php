<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_qualifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 20);
            $t->string('license_level', 150);
            $t->string('certificate_number', 150);
            $t->string('issuer');
            $t->date('issued_at');
            $t->date('expires_at')->nullable();
            $t->string('document_path');
            $t->string('status', 20)->default('pending');
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['status', 'province_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_qualifications');
    }
};
