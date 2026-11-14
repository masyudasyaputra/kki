<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_number_override', 40)->nullable()->unique();
            $table->boolean('member_number_manual')->default(false);
        });
        // Freeze existing numbers at rollout; no ID changes when profile data changes.
        \App\Models\User::where('role', 'member')->with(['province', 'beltLevel'])->chunkById(100, function ($members) {
            foreach ($members as $member) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $member->id)->update(['member_number_override' => $member->automatic_member_number]);
            }
        });
        Schema::create('member_number_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('before_number', 40);
            $table->string('after_number', 40);
            $table->string('mode', 10);
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_number_changes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['member_number_override']);
            $table->dropColumn('member_number_override');
            $table->dropColumn('member_number_manual');
        });
    }
};
