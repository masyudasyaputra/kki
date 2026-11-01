<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MembershipReview;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MembershipVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'verification_test', 'database.connections.verification_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role', 'parent_name', 'whatsapp', 'district'] as $column) $table->string($column)->nullable();
            $table->json('roles')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) $table->integer($column)->nullable();
            $table->string('verification_status')->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        foreach (['provinces', 'cities', 'dojos', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name');
                $table->integer('province_id')->nullable(); $table->integer('city_id')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('membership_verifications', function (Blueprint $table) {
            $table->id(); $table->integer('member_id'); $table->integer('reviewer_id')->nullable();
            $table->string('reviewer_level'); $table->string('status');
            $table->text('notes')->nullable(); $table->json('snapshot'); $table->timestamps();
        });
        DB::table('provinces')->insert([['id' => 1, 'name' => 'SUMATERA UTARA'], ['id' => 2, 'name' => 'JAWA BARAT']]);
        DB::table('cities')->insert([['id' => 1, 'name' => 'MEDAN', 'province_id' => 1], ['id' => 2, 'name' => 'BINJAI', 'province_id' => 1], ['id' => 3, 'name' => 'BANDUNG', 'province_id' => 2]]);
    }

    private function actor(string $role, ?int $province = 1, ?int $city = 1): User
    {
        $user = User::create(['name' => $role, 'email' => uniqid().'@example.com', 'password' => 'password',
            'role' => $role, 'province_id' => $province, 'city_id' => $city]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user->fresh();
    }

    private function member(int $province = 1, int $city = 1): User
    {
        return User::create(['name' => 'Anggota '.$city, 'email' => uniqid().'@example.com', 'role' => 'member',
            'province_id' => $province, 'city_id' => $city, 'district' => 'Medan Johor'])->fresh();
    }

    private function decision(User $member, array $overrides = []): array
    {
        return array_replace(['status' => 'approved', 'checked' => MembershipReview::FIELDS,
            'fingerprint' => app(MembershipReview::class)->fingerprint($member)], $overrides);
    }

    public function test_verifier_levels_only_see_their_scope_and_cannot_open_or_review_outside_it(): void
    {
        $local = $this->member(); $branch = $this->member(1, 2); $outside = $this->member(2, 3);
        foreach (['verifikator_pb' => 3, 'verifikator_dewan_guru' => 3, 'verifikator_pengprov' => 2, 'verifikator_pengcab' => 1] as $role => $count) {
            $actor = $this->actor($role);
            $this->assertSame($count, app(MembershipReview::class)->members($actor)->count());
            $this->actingAs($actor)->get(route('admin.verifications.index'))->assertOk()->assertSee('Verifikasi Keanggotaan');
            $this->get(route('admin.verifications.show', $local))->assertOk()->assertSee('Kecamatan');
            if (!in_array($role, ['verifikator_pb', 'verifikator_dewan_guru'])) {
                $this->get(route('admin.verifications.show', $outside))->assertNotFound();
                $this->post(route('admin.verifications.store', $outside), $this->decision($outside))->assertNotFound();
            }
        }
        $this->actingAs($this->actor('verifikator_pengcab'))->get(route('admin.verifications.show', $branch))->assertNotFound();
        $this->assertSame(0, app(MembershipReview::class)->members($this->actor('verifikator_pengprov', null, null))->count());
    }

    public function test_approval_requires_all_checks_and_keeps_an_audit_without_activating_membership(): void
    {
        $member = $this->member(); $actor = $this->actor('verifikator_pb');
        $this->actingAs($actor)->post(route('admin.verifications.store', $member), $this->decision($member, ['checked' => ['name']]))->assertSessionHasErrors('checked');
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertRedirect(route('admin.verifications.show', $member));
        $this->assertSame('approved', $member->fresh()->verification_status);
        $this->assertFalse($member->fresh()->is_active);
        $entry = $member->membershipVerifications()->firstOrFail();
        $this->assertSame($actor->id, $entry->reviewer_id);
        $this->assertSame('Medan Johor', $entry->snapshot['values']['district']);
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertSessionHasErrors('status');
        $this->assertSame(1, $member->membershipVerifications()->count());
    }

    public function test_revision_requires_notes_and_changed_member_data_reopens_review(): void
    {
        $member = $this->member(); $this->actingAs($this->actor('verifikator_pengcab'));
        $payload = $this->decision($member, ['status' => 'revision', 'checked' => []]);
        $this->post(route('admin.verifications.store', $member), $payload)->assertSessionHasErrors('notes');
        $this->post(route('admin.verifications.store', $member), $payload + ['notes' => 'Perbaiki nama kecamatan'])->assertRedirect();
        $this->assertSame('revision', $member->fresh()->verification_status);
        $member = $member->fresh(); $member->district = 'Medan Baru'; $member->save();
        $this->assertSame('pending', $member->fresh()->verification_status);
        $this->assertSame(1, $member->membershipVerifications()->count());
    }

    public function test_stale_data_cannot_be_approved_and_member_cannot_access_verification(): void
    {
        $member = $this->member(); $payload = $this->decision($member);
        $member->update(['name' => 'Nama diperbaiki']);
        $this->actingAs($this->actor('verifikator_pb'))->post(route('admin.verifications.store', $member), $payload)->assertSessionHasErrors('fingerprint');
        $this->actingAs($member)->get(route('admin.verifications.index'))->assertForbidden();
        $this->assertDatabaseCount('membership_verifications', 0);
    }

    public function test_verifiers_land_on_dedicated_page_and_cannot_access_admin_features(): void
    {
        $this->actingAs($this->actor('verifikator_pengprov'));
        $this->get('/dashboard')->assertRedirect(route('admin.verifications.index'));
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.members.create'))->assertForbidden();
    }

    public function test_provincial_admin_cannot_assign_national_verifier_or_other_province(): void
    {
        $this->actingAs($this->actor('pengprov'));
        $payload = ['name' => 'Tim', 'email' => 'tim@example.com', 'password' => 'Password123!', 'roles' => ['verifikator_pb'], 'province_id' => 1];
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('roles.0');
        $payload['roles'] = ['verifikator_pengprov']; $payload['province_id'] = 2;
        $this->post(route('admin.users.store'), $payload)->assertForbidden();
        $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'tim@example.com', 'role' => 'verifikator_pengprov', 'province_id' => 1]);
    }

    public function test_pb_can_create_national_verifier_without_region_and_branch_verifier_requires_city(): void
    {
        $this->actingAs($this->actor('pb'));
        $payload = ['name' => 'Tim Nasional', 'email' => 'nasional@example.com', 'password' => 'Password123!', 'roles' => ['verifikator_pb']];
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'nasional@example.com', 'role' => 'verifikator_pb', 'province_id' => null]);
        $payload['email'] = 'cabang@example.com'; $payload['roles'] = ['verifikator_pengcab']; $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('city_id');
        $payload['city_id'] = 3;
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('city_id');
        $payload['city_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $target = User::where('email', 'cabang@example.com')->firstOrFail();
        $this->put(route('admin.users.update', $target), $payload + ['is_active' => 1])->assertRedirect(route('admin.users.index'));
        $this->assertSame(1, $target->fresh()->city_id);
    }

    public function test_provincial_admin_cannot_edit_or_delete_a_national_verifier(): void
    {
        $target = $this->actor('verifikator_pb');
        $this->actingAs($this->actor('pengprov'));
        $this->get(route('admin.users.edit', $target))->assertForbidden();
        $this->put(route('admin.users.update', $target), [])->assertForbidden();
        $this->delete(route('admin.users.destroy', $target))->assertForbidden();
    }

    public function test_dewan_guru_can_review_national_members_and_is_managed_only_by_pb(): void
    {
        $this->actingAs($this->actor('pb'));
        $payload = ['name' => 'Dewan Guru', 'email' => 'guru@example.com', 'password' => 'Password123!', 'roles' => ['verifikator_dewan_guru']];
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $guru = User::where('email', 'guru@example.com')->firstOrFail();
        $this->assertNull($guru->province_id);
        $this->put(route('admin.users.update', $guru), $payload + ['is_active' => 1])->assertRedirect(route('admin.users.index'));
        $member = $this->member(2, 3);
        $this->actingAs($guru)->get('/dashboard')->assertRedirect(route('admin.verifications.index'));
        $this->get(route('admin.verifications.show', $member))->assertOk();
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertRedirect();
        $this->assertSame('dewan_guru', $member->membershipVerifications()->firstOrFail()->reviewer_level);
        $this->actingAs($this->actor('pengprov'));
        $this->get(route('admin.users.edit', $guru))->assertForbidden();
        $this->delete(route('admin.users.destroy', $guru))->assertForbidden();
        $payload['email'] = 'guru2@example.com'; $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('roles.0');
    }

    public function test_super_admin_keeps_pb_access_when_verifier_roles_are_submitted_first(): void
    {
        $admin = $this->actor('pb', null, null);
        $admin->update(['roles' => ['pb', 'super_admin', 'verifikator_pb']]);
        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertOk()->assertSee('PB / Super Admin');
        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_active' => 1,
            'roles' => ['verifikator_pb', 'verifikator_pengprov', 'verifikator_pengcab', 'pengprov', 'pengcab', 'admin_dojo', 'penguji'],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $admin = $admin->fresh();
        $this->assertSame('pb', $admin->role);
        $this->assertTrue($admin->hasRole(['pb']));
        $this->assertContains('super_admin', $admin->roles);
        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertOk();
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }
}
