<?php

use App\Services\RoleAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->json('positions')->nullable());
        Schema::create('exam_reviewers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unique(['exam_id', 'user_id']);
            $t->timestamps();
        });
        Schema::create('access_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('target_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('exam_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event'); $t->json('before'); $t->json('after'); $t->timestamps();
        });
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $old = ['role' => $user->role, 'roles' => json_decode($user->roles ?? '[]', true) ?: []];
                $all = RoleAccess::migrateRoles(array_merge([$old['role']], $old['roles']));
                // Preserve existing PB certificate operators as an explicit, revocable permission.
                if (in_array('pb', $all, true)) $all[] = 'pengelola_sertifikat_pb';
                $positions = array_values(array_intersect(array_keys(RoleAccess::POSITIONS), $all));
                if (!$positions) $positions = ['member'];
                $after = ['role' => RoleAccess::primary($positions), 'positions' => $positions,
                    'roles' => array_values(array_intersect(array_keys(RoleAccess::PERMISSIONS), $all))];
                DB::table('users')->where('id', $user->id)->update(['role' => $after['role'], 'positions' => json_encode($positions), 'roles' => json_encode($after['roles'])]);
                DB::table('access_audits')->insert(['target_id' => $user->id, 'event' => 'roles.migrated',
                    'before' => json_encode($old), 'after' => json_encode($after), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $all = array_merge(json_decode($user->positions ?? '[]', true) ?: [], json_decode($user->roles ?? '[]', true) ?: []);
                DB::table('users')->where('id', $user->id)->update(['roles' => json_encode(array_values(array_unique($all)))]);
            }
        });
        Schema::dropIfExists('access_audits');
        Schema::dropIfExists('exam_reviewers');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('positions'));
    }
};
