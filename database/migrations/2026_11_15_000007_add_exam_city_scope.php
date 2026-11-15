<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('exams', fn (Blueprint $t) => $t->foreignId('city_id')->nullable()->constrained()->nullOnDelete()); }
    public function down(): void { Schema::table('exams', function (Blueprint $t) { $t->dropForeign(['city_id']); $t->dropColumn('city_id'); }); }
};
