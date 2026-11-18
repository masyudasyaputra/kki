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
            foreach (['name', 'email', 'password', 'role', 'parent_name', 'whatsapp', 'district', 'first_coach', 'photo'] as $column) $table->string($column)->nullable();
            $table->json('roles')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) $table->integer($column)->nullable();
            $table->string('verification_status')->default('pending');
            $table->string('registration_type')->default('karateka');
            $table->boolean('is_active')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        foreach (['provinces', 'cities', 'dojos', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->boolean('is_active')->default(true); $table->id(); $table->string('name');
                if ($name === 'belt_levels') $table->string('kyu_dan')->nullable();
                $table->integer('province_id')->nullable(); $table->integer('city_id')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('membership_verifications', function (Blueprint $table) {
            $table->id(); $table->integer('member_id'); $table->integer('reviewer_id')->nullable();
            $table->string('reviewer_level'); $table->string('status');
            $table->text('notes')->nullable(); $table->json('snapshot'); $table->timestamps();
        });
        Schema::create('officials', function (Blueprint $table) {
            $table->id(); $table->integer('user_id')->nullable()->unique();
        });
        Schema::create('exams', function (Blueprint $t) { $t->id(); });
        (require database_path('migrations/2026_11_09_000002_restructure_roles_and_exam_assignments.php'))->up();
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

    public function test_official_applications_are_excluded_from_membership_list_badge_detail_and_approval(): void
    {
        $karateka = $this->member();
        $official = $this->member();
        $official->update(['registration_type' => 'official', 'name' => 'Calon Pengurus Non Karateka']);
        $this->actingAs($this->actor('verifikator_anggota_pb'));
        $this->get(route('admin.verifications.index'))->assertOk()
            ->assertSee($karateka->name)->assertDontSee('Calon Pengurus Non Karateka')
            ->assertSee('aria-label="1 pengajuan menunggu verifikasi"', false);
        $this->get(route('admin.verifications.show', $official))->assertNotFound();
        $this->post(route('admin.verifications.store', $official), [
            'status' => 'approved', 'checked' => MembershipReview::FIELDS,
            'fingerprint' => app(MembershipReview::class)->fingerprint($official),
        ])->assertNotFound();
        $this->assertDatabaseCount('membership_verifications', 0);
        $this->assertSame('pending', $official->fresh()->verification_status);
    }

    public function test_verification_list_and_detail_display_the_members_photo(): void
    {
        $member = $this->member();
        $member->photo = 'member-photos/'.$member->id.'/pasfoto.jpg';
        $member->save();
        $this->actingAs($this->actor('verifikator_anggota_pb'));
        $this->get(route('admin.verifications.index'))->assertOk()->assertSee(asset('storage/'.$member->photo));
        $this->get(route('admin.verifications.show', $member))->assertOk()->assertSee(asset('storage/'.$member->photo));
    }

    public function test_member_detail_page_is_scoped_and_available_to_super_admin(): void
    {
        $local = $this->member(1, 1); $outside = $this->member(2, 3);
        $this->actingAs($this->actor('super_admin'))->get(route('admin.members.show', $outside))
            ->assertOk()->assertSee('Alamat dan kontak')->assertSee($outside->name);
        $this->get(route('admin.members.index'))->assertOk()->assertSee(route('admin.members.show', $outside));
        $this->actingAs($this->actor('pengcab'))->get(route('admin.members.show', $local))->assertOk();
        $this->get(route('admin.members.show', $outside))->assertNotFound();
        $this->actingAs($local)->get(route('admin.members.show', $outside))->assertForbidden();
    }

    private function decision(User $member, array $overrides = []): array
    {
        return array_replace(['status' => 'approved', 'checked' => MembershipReview::FIELDS,
            'fingerprint' => app(MembershipReview::class)->fingerprint($member)], $overrides);
    }

    public function test_approval_of_black_belt_adds_msh_without_changing_the_existing_number(): void
    {
        (require database_path('migrations/2026_11_14_000001_add_member_number_management.php'))->up();
        DB::table('belt_levels')->insert(['id' => 1, 'name' => 'Hitam', 'is_active' => true]);
        $member = $this->member();
        $before = $member->member_number;
        $member->update(['belt_level_id' => 1]);
        $this->assertSame($before, $member->fresh()->member_number);
        $this->actingAs($actor = $this->actor('verifikator_anggota_pb'))
            ->post(route('admin.verifications.store', $member), $this->decision($member->fresh()))->assertSessionHasNoErrors();
        $this->assertSame('MSH-'.$before, $member->fresh()->member_number);
        $this->assertDatabaseHas('member_number_changes', ['member_id' => $member->id, 'actor_id' => $actor->id, 'before_number' => $before, 'after_number' => 'MSH-'.$before]);
    }

    public function test_sidebar_badge_counts_only_pending_members_in_the_verifiers_scope(): void
    {
        $this->member();
        $this->member(1, 2);
        $this->member(2, 3);
        $approved = $this->member();
        $revision = $this->member();
        DB::table('users')->where('id', $approved->id)->update(['verification_status' => 'approved']);
        DB::table('users')->where('id', $revision->id)->update(['verification_status' => 'revision']);
        foreach (['verifikator_anggota_pb' => 3, 'verifikator_pengprov' => 2, 'verifikator_pengcab' => 1, 'super_admin' => 3] as $role => $count) {
            $this->actingAs($this->actor($role))->get(route('admin.verifications.index', ['status' => 'approved', 'search' => 'Tidak cocok']))
                ->assertOk()->assertSee('aria-label="'.$count.' pengajuan menunggu verifikasi"', false)
                ->assertSee('aria-label="'.$count.' pengajuan keanggotaan menunggu verifikasi"', false);
        }
    }

    public function test_sidebar_badge_is_hidden_when_no_pending_members_are_visible(): void
    {
        $member = $this->member(2, 3);
        $this->actingAs($this->actor('verifikator_pengprov'))->get(route('admin.verifications.index'))
            ->assertOk()->assertDontSee('sidebar-notification-badge', false);
        DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved']);
        $this->actingAs($this->actor('verifikator_anggota_pb'))->get(route('admin.verifications.index'))
            ->assertOk()->assertDontSee('sidebar-notification-badge', false);
    }

    public function test_black_belt_id_is_displayed_and_searchable_with_separate_contact_icons(): void
    {
        DB::table('belt_levels')->insert(['id' => 1, 'name' => 'Hitam', 'kyu_dan' => 'DAN II']);
        $member = $this->member();
        $member->update(['belt_level_id' => 1, 'whatsapp' => '081234567890']);
        $number = $member->fresh()->member_number;
        $this->assertStringStartsWith('MSH-SMT-', $number);
        $this->actingAs($this->actor('verifikator_anggota_pb'))->get(route('admin.verifications.index', ['search' => $number]))
            ->assertOk()->assertSee($member->name)
            ->assertSee('Hitam (DAN II)')
            ->assertSee('aria-label="ID anggota: '.$number.'"', false)
            ->assertSee('aria-label="Nomor WhatsApp: 081234567890"', false);
        $this->get(route('admin.verifications.index', ['search' => str_replace('MSH-', '', $number)]))->assertOk()->assertSee($member->name);
        $member->update(['belt_level_id' => null]);
        $this->get(route('admin.verifications.index', ['search' => $number]))->assertOk()->assertSee('Tidak ada pendaftaran pada status ini.');
    }

    public function test_verifier_levels_only_see_their_scope_and_cannot_open_or_review_outside_it(): void
    {
        $local = $this->member(); $branch = $this->member(1, 2); $outside = $this->member(2, 3);
        foreach (['verifikator_anggota_pb' => 3, 'verifikator_pengprov' => 2, 'verifikator_pengcab' => 1] as $role => $count) {
            $actor = $this->actor($role);
            $this->assertSame($count, app(MembershipReview::class)->members($actor)->count());
            $this->actingAs($actor)->get(route('admin.verifications.index'))->assertOk()->assertSee('Verifikasi Keanggotaan');
            $this->get(route('admin.verifications.show', $local))->assertOk()->assertSee('Kecamatan');
            if (!in_array($role, ['verifikator_anggota_pb', 'dewan_guru_pusat', 'verifikator_anggota_pb'])) {
                $this->get(route('admin.verifications.show', $outside))->assertNotFound();
                $this->post(route('admin.verifications.store', $outside), $this->decision($outside))->assertNotFound();
            }
        }
        $this->actingAs($this->actor('verifikator_pengcab'))->get(route('admin.verifications.show', $branch))->assertNotFound();
        $this->assertSame(0, app(MembershipReview::class)->members($this->actor('verifikator_pengprov', null, null))->count());
    }

    public function test_approval_requires_all_checks_keeps_an_audit_and_activates_membership(): void
    {
        $member = $this->member(); $actor = $this->actor('verifikator_anggota_pb');
        $this->actingAs($actor)->post(route('admin.verifications.store', $member), $this->decision($member, ['checked' => ['name']]))->assertSessionHasErrors('checked');
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertRedirect(route('admin.verifications.show', $member));
        $this->assertSame('approved', $member->fresh()->verification_status);
        $this->assertTrue($member->fresh()->is_active);
        $entry = $member->membershipVerifications()->firstOrFail();
        $this->assertSame($actor->id, $entry->reviewer_id);
        $this->assertSame('Medan Johor', $entry->snapshot['values']['district']);
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertSessionHasErrors('status');
        $this->assertSame(1, $member->membershipVerifications()->count());
    }

    public function test_regional_scope_is_not_widened_by_forged_region_filters_or_an_unrelated_position(): void
    {
        $local = $this->member(1, 1); $otherCity = $this->member(1, 2); $outside = $this->member(2, 3);
        $regional = $this->actor('member', 1, 1);
        $regional->update(['positions' => ['member'], 'roles' => ['verifikator_pengcab']]);
        $this->actingAs($regional)->get(route('admin.verifications.index', ['province_id' => 2, 'city_id' => 3]))->assertOk()->assertDontSee($outside->email)->assertDontSee($otherCity->email);
        $this->get(route('admin.verifications.show', $otherCity))->assertNotFound();
        $this->post(route('admin.verifications.store', $outside), $this->decision($outside) + ['province_id' => 2, 'city_id' => 3])->assertNotFound();
        $regional->update(['positions' => ['pb']]);
        $this->assertSame(4, app(MembershipReview::class)->members($regional)->count());
        $regional->update(['roles' => ['verifikator_anggota_pb']]);
        $this->assertSame(4, app(MembershipReview::class)->members($regional)->count());
        $this->get(route('admin.verifications.show', $outside))->assertOk();
    }

    public function test_revision_requires_notes_and_changed_member_data_reopens_review(): void
    {
        $member = $this->member(); $this->actingAs($this->actor('verifikator_pengcab'));
        $payload = $this->decision($member, ['status' => 'revision', 'checked' => []]);
        $this->post(route('admin.verifications.store', $member), $payload)->assertSessionHasErrors('notes');
        $this->post(route('admin.verifications.store', $member), $payload + ['notes' => 'Perbaiki nama kecamatan'])->assertRedirect();
        $this->assertSame('revision', $member->fresh()->verification_status);
        $this->assertFalse($member->fresh()->is_active);
        $member = $member->fresh(); $member->district = 'Medan Baru'; $member->save();
        $this->assertSame('pending', $member->fresh()->verification_status);
        $this->assertSame(1, $member->membershipVerifications()->count());
    }

    public function test_stale_data_cannot_be_approved_and_member_cannot_access_verification(): void
    {
        $member = $this->member(); $payload = $this->decision($member);
        $member->update(['name' => 'Nama diperbaiki']);
        $this->actingAs($this->actor('verifikator_anggota_pb'))->post(route('admin.verifications.store', $member), $payload)->assertSessionHasErrors('fingerprint');
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
        $payload = ['name' => 'Tim', 'email' => 'tim@example.com', 'password' => 'Password123!', 'roles' => ['verifikator_anggota_pb'], 'province_id' => 1];
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('roles.0');
        $payload['roles'] = ['verifikator_pengprov']; $payload['province_id'] = 2;
        $this->post(route('admin.users.store'), $payload)->assertForbidden();
        $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'tim@example.com', 'role' => 'member', 'province_id' => 1]);
    }

    public function test_pb_can_create_national_verifier_without_region_and_branch_verifier_requires_city(): void
    {
        $this->actingAs($this->actor('pb'));
        $payload = ['name' => 'Tim Nasional', 'email' => 'nasional@example.com', 'password' => 'Password123!', 'roles' => ['verifikator_anggota_pb']];
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'nasional@example.com', 'role' => 'member', 'province_id' => null]);
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
        $target = $this->actor('verifikator_anggota_pb');
        $this->actingAs($this->actor('pengprov'));
        $this->get(route('admin.users.edit', $target))->assertForbidden();
        $this->put(route('admin.users.update', $target), [])->assertForbidden();
        $this->delete(route('admin.users.destroy', $target))->assertForbidden();
    }

    public function test_dewan_guru_can_review_national_members_and_is_managed_only_by_pb(): void
    {
        $this->actingAs($this->actor('pb'));
        $payload = ['name' => 'Dewan Guru', 'email' => 'guru@example.com', 'password' => 'Password123!', 'roles' => ['dewan_guru_pusat', 'verifikator_anggota_pb']];
        $this->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));
        $guru = User::where('email', 'guru@example.com')->firstOrFail();
        $this->assertNull($guru->province_id);
        $this->put(route('admin.users.update', $guru), $payload + ['is_active' => 1])->assertRedirect(route('admin.users.index'));
        $member = $this->member(2, 3);
        $this->actingAs($guru)->get('/dashboard')->assertRedirect(route('admin.oversight'));
        $this->get(route('admin.verifications.show', $member))->assertOk();
        $this->post(route('admin.verifications.store', $member), $this->decision($member))->assertRedirect();
        $this->assertSame('pb', $member->membershipVerifications()->firstOrFail()->reviewer_level);
        $this->actingAs($this->actor('pengprov'));
        $this->get(route('admin.users.edit', $guru))->assertForbidden();
        $this->delete(route('admin.users.destroy', $guru))->assertForbidden();
        $payload['email'] = 'guru2@example.com'; $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('roles.0');
    }

    public function test_super_admin_keeps_pb_access_when_verifier_roles_are_submitted_first(): void
    {
        $admin = $this->actor('pb', null, null);
        $admin->update(['roles' => ['pb', 'super_admin', 'verifikator_anggota_pb']]);
        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertOk()->assertSee('Pengurus Pusat');
        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_active' => 1,
            'positions' => ['pb', 'super_admin'], 'roles' => ['verifikator_anggota_pb'],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $admin = $admin->fresh();
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue($admin->hasRole(['pb']));
        $this->assertContains('super_admin', $admin->positions);
        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertOk();
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }
}
