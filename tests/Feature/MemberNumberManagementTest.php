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
