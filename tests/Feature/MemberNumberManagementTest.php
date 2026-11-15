<?php

namespace Tests\Feature;

use App\Models\{MemberNumberChange, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class MemberNumberManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'number_test', 'database.connections.number_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password')->nullable();
            $t->string('role'); $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->boolean('is_active')->default(false); $t->string('verification_status')->default('approved');
            $t->timestamp('email_verified_at')->nullable(); $t->string('photo')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $key) $t->integer($key)->nullable();
            $t->timestamps();
        });
        Schema::create('provinces', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('belt_levels', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('kyu_dan')->nullable(); });
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Sumatera Utara']);
        DB::table('belt_levels')->insert(['id' => 1, 'name' => 'Hitam', 'kyu_dan' => 'DAN IV']);
        (require database_path('migrations/2026_11_14_000001_add_member_number_management.php'))->up();
    }

    private function user(string $role = 'member'): User
    {
        $user = User::create(['name' => 'Akun '.uniqid(), 'email' => uniqid().'@example.test', 'role' => $role, 'province_id' => 1, 'belt_level_id' => 1]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user->fresh();
    }

    private function change(User $member, array $overrides = []): array
    {
        return array_replace(['mode' => 'manual', 'number' => 'KKI-ARSIP-001', 'reason' => 'Penyesuaian arsip', 'expected_number' => $member->member_number], $overrides);
    }

    public function test_non_karateka_registration_does_not_issue_a_member_number(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('registration_type')->default('karateka'));
        $official = User::create(['name' => 'Pengurus', 'email' => 'pengurus@example.test', 'role' => 'member', 'registration_type' => 'official']);
        $this->assertNull($official->fresh()->member_number_override);
        $this->assertNotNull($this->user()->member_number_override);
    }

    public function test_non_black_members_cannot_receive_msh_manually_or_through_swap(): void
    {
        DB::table('belt_levels')->insert(['id' => 2, 'name' => 'Coklat', 'kyu_dan' => 'KYU I']);
        $black = $this->user(); $colored = $this->user();
        $colored->update(['belt_level_id' => 2]);
        $this->assertStringNotContainsString('MSH-', $colored->fresh()->member_number);
        $before = $colored->fresh()->member_number;
        $this->actingAs($admin = $this->user('super_admin'));
        $this->put(route('admin.member-numbers.update', $colored), $this->change($colored->fresh(), ['number' => 'MSH-SMT-008888']))->assertSessionHasErrors('number');
        foreach ([[$colored->fresh(), $black->fresh()], [$black->fresh(), $colored->fresh()]] as [$first, $second]) {
            $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['mode' => 'swap', 'target_number' => $second->member_number]))->assertSessionHasErrors('target_number');
        }
        $this->assertSame($before, $colored->fresh()->member_number);
        $this->assertStringStartsWith('MSH-', $black->fresh()->member_number);
    }

    public function test_belt_correction_removes_msh_and_keeps_an_audit_record(): void
    {
        DB::table('belt_levels')->insert(['id' => 2, 'name' => 'Putih', 'kyu_dan' => 'KYU VII']);
        $member = $this->user(); $old = $member->member_number;
        $member->load('beltLevel');
        $member->update(['belt_level_id' => 2]);
        $this->assertSame(substr($old, 4), $member->fresh()->member_number);
        $this->assertDatabaseHas('member_number_changes', ['member_id' => $member->id, 'before_number' => $old, 'after_number' => substr($old, 4)]);
    }

    public function test_promotion_adds_msh_only_when_confirmed_preserves_base_number_and_is_idempotent(): void
    {
        DB::table('belt_levels')->insert(['id' => 2, 'name' => 'Coklat', 'kyu_dan' => 'KYU I']);
        $member = $this->user();
        $member->update(['belt_level_id' => 2]);
        $before = $member->fresh()->member_number;
        $member->update(['belt_level_id' => 1]);
        $this->assertSame($before, $member->fresh()->member_number);
        $this->assertSame('pending', $member->fresh()->verification_status);
        $admin = $this->user('super_admin');
        $numbers = app(\App\Services\MemberNumbers::class);
        $numbers->confirmBlackBelt($member, $admin);
        $numbers->confirmBlackBelt($member->fresh(), $admin);
        $this->assertSame('MSH-'.$before, $member->fresh()->member_number);
        $this->assertSame(1, \App\Models\MemberNumberChange::where('member_id', $member->id)->where('reason', 'Penambahan kode MSH setelah pengesahan sabuk hitam.')->count());
    }

    public function test_registration_claiming_black_belt_waits_for_verification_before_msh(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('verification_status'));
        Schema::table('users', fn (Blueprint $t) => $t->string('verification_status')->default('pending'));
        $member = User::create(['name' => 'Calon Hitam', 'email' => 'claim@example.test', 'role' => 'member', 'province_id' => 1, 'belt_level_id' => 1]);
        $this->assertStringStartsWith('SMT-', $member->fresh()->member_number);
    }

    public function test_conflicting_msh_number_blocks_confirmation_without_overwriting_any_id(): void
    {
        $member = $this->user(); $other = $this->user(); $admin = $this->user('super_admin');
        $originalMsh = $member->member_number;
        $numbers = app(\App\Services\MemberNumbers::class);
        $numbers->change($member, $admin, ['mode' => 'manual', 'number' => substr($originalMsh, 4), 'expected_number' => $originalMsh, 'reason' => 'Nomor awal']);
        $numbers->change($other, $admin, ['mode' => 'manual', 'number' => $originalMsh, 'expected_number' => $other->member_number, 'reason' => 'Nomor arsip']);
        try {
            $numbers->confirmBlackBelt($member->fresh(), $admin);
            $this->fail('Duplicate MSH should not be accepted');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('number', $exception->errors());
        }
        $this->assertSame(substr($originalMsh, 4), $member->fresh()->member_number);
        $this->assertSame($originalMsh, $other->fresh()->member_number);
        $this->assertDatabaseCount('member_number_changes', 2);
    }

    public function test_existing_invalid_msh_numbers_are_repaired_without_collisions(): void
    {
        DB::table('belt_levels')->insert(['id' => 2, 'name' => 'Hijau', 'kyu_dan' => 'KYU V']);
        $member = $this->user(); $black = $this->user();
        $oldBlack = $black->member_number;
        DB::table('users')->where('id', $member->id)->update(['belt_level_id' => 2]);
        DB::table('users')->where('id', $black->id)->update(['member_number_override' => substr($member->member_number, 4)]);
        (require database_path('migrations/2026_11_15_000005_repair_non_black_msh_numbers.php'))->up();
        $this->assertStringNotContainsString('MSH-', $member->fresh()->member_number);
        $this->assertNotSame($member->fresh()->member_number, $black->fresh()->member_number);
        $this->assertDatabaseHas('member_number_changes', ['member_id' => $member->id, 'reason' => 'Penyesuaian kode MSH: hanya untuk anggota sabuk hitam.']);
    }

    public function test_only_super_admin_can_view_and_change_member_numbers(): void
    {
        $member = $this->user();
        $this->get(route('admin.member-numbers.index'))->assertRedirect(route('login'));
        foreach (['pb', 'pengprov', 'admin_pengprov', 'verifikator_anggota_pb', 'member'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('admin.member-numbers.index'))->assertForbidden();
            $this->get(route('admin.member-numbers.edit', $member))->assertForbidden();
            $this->put(route('admin.member-numbers.update', $member), $this->change($member))->assertForbidden();
        }
        $this->actingAs($this->user('super_admin'))->get(route('admin.member-numbers.index'))->assertOk()->assertSee('Manajemen Nomor ID');
        $this->get(route('admin.member-numbers.edit', $member))->assertOk();
        $this->assertDatabaseCount('member_number_changes', 0);
    }

    public function test_manual_change_is_normalized_searchable_and_audited_without_changing_account_or_verification(): void
    {
        $member = $this->user(); $before = $member->member_number;
        $this->actingAs($actor = $this->user('super_admin'));
        $this->put(route('admin.member-numbers.update', $member), $this->change($member, ['number' => ' kki-arsip-001 ']))->assertSessionHasNoErrors()->assertRedirect();
        $fresh = $member->fresh();
        $this->assertSame('KKI-ARSIP-001', $fresh->member_number);
        $this->assertSame($member->id, $fresh->id);
        $this->assertSame('approved', $fresh->verification_status);
        $this->assertTrue((bool) $fresh->member_number_manual);
        $this->assertSame($fresh->id, User::whereMemberNumber('kki-arsip-001')->sole()->id);
        $this->assertFalse(User::whereMemberNumber($before)->exists());
        $this->get(route('admin.member-numbers.index', ['search' => 'KKI-ARSIP-001']))->assertOk()->assertSee($member->name);
        $this->assertDatabaseHas('member_number_changes', ['actor_id' => $actor->id, 'member_id' => $member->id, 'before_number' => $before, 'after_number' => 'KKI-ARSIP-001', 'reason' => 'Penyesuaian arsip']);
    }

    public function test_number_editor_supports_modal_validation_and_success_redirects(): void
    {
        $member = $this->user();
        $this->actingAs($this->user('super_admin'));
        $this->get(route('admin.member-numbers.index'))->assertOk()->assertSee('data-member-number-modal', false);
        $this->get(route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => 1]))
            ->assertOk()->assertSee('form-modal-body')->assertDontSee('id="backend-sidebar"', false)->assertSee('_form_modal=1', false);
        $this->from(route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => 1]))
            ->put(route('admin.member-numbers.update', ['member' => $member, '_form_modal' => 1]), $this->change($member, ['reason' => '']))
            ->assertSessionHasErrors('reason')->assertRedirect(route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => 1]));
        $this->put(route('admin.member-numbers.update', ['member' => $member, '_form_modal' => 1]), $this->change($member))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => 1]));
        $this->get(route('admin.member-numbers.edit', ['member' => $member, '_form_modal' => 1]))
            ->assertOk()->assertSee('data-modal-status="saved"', false)->assertDontSee('id="backend-sidebar"', false);
    }

    public function test_duplicate_stale_and_unexplained_changes_are_rejected(): void
    {
        $first = $this->user(); $second = $this->user();
        $this->actingAs($this->user('super_admin'));
        $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['number' => $second->member_number]))->assertSessionHasErrors('number');
        $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['expected_number' => 'STALE-001']))->assertSessionHasErrors('expected_number');
        $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['reason' => ' ']))->assertSessionHasErrors('reason');
        $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['number' => '<script>']))->assertSessionHasErrors('number');
        $this->assertDatabaseCount('member_number_changes', 0);
    }

    public function test_swap_exchanges_both_numbers_atomically_and_records_both_changes(): void
    {
        $first = $this->user(); $second = $this->user();
        $a = $first->member_number; $b = $second->member_number;
        $this->actingAs($this->user('super_admin'));
        $this->put(route('admin.member-numbers.update', $first), $this->change($first, ['mode' => 'swap', 'target_number' => $b]))->assertSessionHasNoErrors();
        $this->assertSame($b, $first->fresh()->member_number);
        $this->assertSame($a, $second->fresh()->member_number);
        $this->assertSame($first->id, User::whereMemberNumber($b)->sole()->id);
        $this->assertDatabaseCount('member_number_changes', 2);
        $this->assertSame(['swap'], MemberNumberChange::pluck('mode')->unique()->values()->all());
        $this->put(route('admin.member-numbers.update', $first), $this->change($first->fresh(), ['mode' => 'swap', 'target_number' => 'UNKNOWN-001']))->assertSessionHasErrors('target_number');
        $this->assertDatabaseCount('member_number_changes', 2);
    }

    public function test_automatic_reset_and_future_registration_cannot_reuse_an_occupied_number(): void
    {
        $first = $this->user();
        $this->actingAs($this->user('super_admin'));
        $this->put(route('admin.member-numbers.update', $first), $this->change($first))->assertSessionHasNoErrors();
        $this->put(route('admin.member-numbers.update', $first), $this->change($first->fresh(), ['mode' => 'auto']))->assertSessionHasNoErrors();
        $this->assertSame($first->automatic_member_number, $first->fresh()->member_number);
        $this->assertFalse((bool) $first->fresh()->member_number_manual);
        $nextId = User::max('id') + 1;
        $reserved = 'MSH-SMT-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        $this->put(route('admin.member-numbers.update', $first), $this->change($first->fresh(), ['number' => $reserved]))->assertSessionHasNoErrors();
        $new = $this->user();
        $this->assertNotSame($reserved, $new->member_number);
        $this->assertNotNull($new->member_number_override);
    }

    public function test_profile_changes_preserve_issued_number_and_impersonation_cannot_change_it(): void
    {
        $member = $this->user(); $number = $member->member_number;
        $member->province_id = null; $member->save();
        $this->assertSame($number, $member->fresh()->member_number);
        $this->actingAs($this->user('super_admin'))->withSession(['impersonation' => ['original_id' => 1]]);
        $this->put(route('admin.member-numbers.update', $member), $this->change($member))->assertForbidden();
        $this->assertSame($number, $member->fresh()->member_number);
    }

    public function test_rollout_preserves_legacy_numbers_and_swap_rolls_back_if_audit_cannot_be_saved(): void
    {
        $migration = require database_path('migrations/2026_11_14_000001_add_member_number_management.php');
        $migration->down();
        $first = $this->user(); $before = $first->member_number;
        $migration->up();
        $this->assertSame($before, $first->fresh()->member_number);
        $second = $this->user(); $other = $second->member_number;
        $actor = $this->user('super_admin');
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.MemberNumberChange::class, function () { throw new \RuntimeException('Audit unavailable'); });
        try {
            app(\App\Services\MemberNumbers::class)->swap($first->fresh(), $actor, ['target_number' => $other, 'expected_number' => $before, 'reason' => 'Koreksi nomor']);
            $this->fail('Swap should fail when its audit cannot be recorded.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        } finally {
            \Illuminate\Support\Facades\Event::forget('eloquent.creating: '.MemberNumberChange::class);
        }
        $this->assertSame($before, $first->fresh()->member_number);
        $this->assertSame($other, $second->fresh()->member_number);
        $this->assertDatabaseCount('member_number_changes', 0);
    }
}
