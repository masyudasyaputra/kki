<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class RoleMigrationTest extends TestCase
{
    public function test_existing_accounts_keep_their_region_and_receive_separate_explicit_permissions(): void
    {
        config(['database.default' => 'role_migration_test', 'database.connections.role_migration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('role'); $t->json('roles')->nullable();
            $t->integer('province_id')->nullable(); $t->integer('city_id')->nullable();
        });
        Schema::create('exams', fn (Blueprint $t) => $t->id());
        DB::table('users')->insert([
            ['id' => 1, 'role' => 'pb', 'roles' => json_encode(['pb', 'super_admin']), 'province_id' => null, 'city_id' => null],
            ['id' => 2, 'role' => 'verifikator_pb', 'roles' => null, 'province_id' => null, 'city_id' => null],
            ['id' => 3, 'role' => 'verifikator_dewan_guru', 'roles' => null, 'province_id' => null, 'city_id' => null],
            ['id' => 4, 'role' => 'verifikator_pengcab', 'roles' => null, 'province_id' => 1, 'city_id' => 7],
        ]);
        (require database_path('migrations/2026_11_09_000002_restructure_roles_and_exam_assignments.php'))->up();
        $users = DB::table('users')->get()->keyBy('id');
        $this->assertSame('super_admin', $users[1]->role);
        $this->assertEqualsCanonicalizing(['pb', 'super_admin'], json_decode($users[1]->positions, true));
        $this->assertSame(['pengelola_sertifikat_pb'], json_decode($users[1]->roles, true));
        $this->assertSame(['verifikator_anggota_pb'], json_decode($users[2]->roles, true));
        $this->assertSame('dewan_guru_pusat', $users[3]->role);
        $this->assertNotContains('verifikator_ujian_pb', json_decode($users[3]->roles, true));
        $this->assertSame(1, $users[4]->province_id);
        $this->assertSame(7, $users[4]->city_id);
        $this->assertSame(['verifikator_pengcab'], json_decode($users[4]->roles, true));
        $this->assertSame('member', $users[4]->role);
        $this->assertDatabaseCount('access_audits', 4);
        $this->assertDatabaseCount('exam_reviewers', 0);
    }
}
