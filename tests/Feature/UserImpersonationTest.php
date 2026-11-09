<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserImpersonation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'impersonation_test', 'database.connections.impersonation_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password')->nullable();
            $table->string('role'); $table->json('roles')->nullable(); $table->boolean('is_active')->default(true);
            $table->integer('province_id')->nullable(); $table->integer('city_id')->nullable(); $table->integer('dojo_id')->nullable();
            $table->timestamp('email_verified_at')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        foreach (['provinces', 'cities', 'dojos'] as $name) Schema::create($name, function (Blueprint $table) { $table->id(); $table->string('name'); });
        (require database_path('migrations/2026_11_01_000005_create_user_impersonations.php'))->up();
    }

    private function user(string $role, int $province = 1, array $extra = []): User
    {
        $user = User::create(array_replace(['name' => 'Akun '.$role, 'email' => uniqid().'@example.com', 'role' => $role, 'roles' => [], 'is_active' => true, 'province_id' => $province], $extra));
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }

    public function test_pb_can_switch_account_and_return_without_changing_roles_or_password(): void
    {
        $admin = $this->user('pb'); $member = $this->user('member');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time(), 'google_signup' => ['email' => 'old@example.com']]);
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Login sebagai '.$member->name);
        $this->post(route('admin.users.login-as', $member))->assertRedirect(route('dashboard'))->assertSessionHas('impersonation.actor_id', $admin->id)->assertSessionMissing('auth.password_confirmed_at')->assertSessionMissing('google_signup');
        $this->assertAuthenticatedAs($member);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Login sebagai '.$member->name)->assertSee('Kembali ke '.$admin->name);
        $this->post(route('impersonation.stop'), ['actor_id' => $member->id])->assertRedirect(route('admin.users.index'))->assertSessionMissing('impersonation');
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('pb', $admin->fresh()->role); $this->assertSame('member', $member->fresh()->role);
        $this->assertDatabaseHas('user_impersonations', ['actor_id' => $admin->id, 'target_id' => $member->id, 'end_reason' => 'returned']);
    }

    public function test_provincial_admin_can_only_impersonate_lower_accounts_in_same_province(): void
    {
        $admin = $this->user('pengprov'); $local = $this->user('member');
        $denied = [$this->user('member', 2), $this->user('pb'), $this->user('pengprov'), $this->user('verifikator_anggota_pb'), $this->user('dewan_guru_pusat'), $this->user('verifikator_ujian_pb'), $this->user('pengelola_sertifikat_pb'), $this->user('member', 1, ['roles' => ['pb']])];
        $this->actingAs($admin);
        foreach ($denied as $target) $this->post(route('admin.users.login-as', $target))->assertForbidden();
        $this->assertDatabaseCount('user_impersonations', 0);
        $this->post(route('admin.users.login-as', $local))->assertRedirect();
        $this->assertAuthenticatedAs($local);
    }

    public function test_nested_self_and_unprivileged_switches_are_rejected(): void
    {
        $admin = $this->user('pb'); $target = $this->user('pengprov'); $member = $this->user('member');
        $this->actingAs($member)->post(route('admin.users.login-as', $target))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.login-as', $admin))->assertForbidden();
        $this->post(route('admin.users.login-as', $target))->assertRedirect();
        $this->post(route('admin.users.login-as', $member))->assertForbidden();
        $this->post(route('impersonation.stop'))->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('user_impersonations', 1);
    }

    public function test_logout_ends_impersonation_and_does_not_restore_admin(): void
    {
        $admin = $this->user('pb'); $member = $this->user('member');
        $this->actingAs($admin)->post(route('admin.users.login-as', $member))->assertRedirect();
        $this->post(route('logout'))->assertRedirect('/')->assertSessionMissing('impersonation');
        $this->assertGuest();
        $this->assertDatabaseHas('user_impersonations', ['actor_id' => $admin->id, 'target_id' => $member->id, 'end_reason' => 'logout']);
    }

    public function test_revoked_original_account_cannot_be_restored(): void
    {
        $admin = $this->user('pb'); $member = $this->user('member');
        $this->actingAs($admin)->post(route('admin.users.login-as', $member))->assertRedirect();
        $admin->update(['role' => 'member']);
        $this->post(route('impersonation.stop'))->assertRedirect(route('login'))->assertSessionMissing('impersonation');
        $this->assertGuest();
        $this->assertDatabaseHas('user_impersonations', ['end_reason' => 'actor_unavailable']);
    }

    public function test_return_requires_valid_impersonation_and_uses_post_only(): void
    {
        $admin = $this->user('pb'); $member = $this->user('member');
        $this->actingAs($admin)->get(route('admin.users.login-as', $member))->assertMethodNotAllowed();
        $this->post(route('impersonation.stop'))->assertForbidden();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_member_with_unpaid_membership_can_be_impersonated(): void
    {
        $admin = $this->user('pb'); $member = $this->user('member', 1, ['is_active' => false]);
        $this->actingAs($admin)->post(route('admin.users.login-as', $member))->assertRedirect();
        $this->assertAuthenticatedAs($member);
        $this->assertFalse($member->fresh()->is_active);
    }
}
