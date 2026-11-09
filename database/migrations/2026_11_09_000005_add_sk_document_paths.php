<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('sk_document_path')->nullable();
        });

        Schema::table('officials', function (Blueprint $table) {
            $table->string('sk_document_path')->nullable();
        });

        Schema::table('dojos', function (Blueprint $table) {
            $table->string('sk_document_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('provinces', fn (Blueprint $table) => $table->dropColumn('sk_document_path'));
        Schema::table('officials', fn (Blueprint $table) => $table->dropColumn('sk_document_path'));
        Schema::table('dojos', fn (Blueprint $table) => $table->dropColumn('sk_document_path'));
    }
};
