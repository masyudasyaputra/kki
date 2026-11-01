<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_certificates', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->index();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('exam_participants', fn (Blueprint $table) => $table->json('certificate_identity')->nullable());
    }
    public function down(): void
    {
        Schema::table('exam_certificates', function (Blueprint $table) { $table->dropConstrainedForeignId('sent_by'); $table->dropColumn('sent_at'); });
        Schema::table('exam_participants', fn (Blueprint $table) => $table->dropColumn('certificate_identity'));
    }
};
