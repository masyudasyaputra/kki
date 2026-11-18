<?php

namespace Tests\Feature;

use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrivacyCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'privacy_test', 'database.connections.privacy_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $this->withoutVite();
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('role');
            $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->string('verification_status')->default('pending');
            $t->boolean('is_active')->default(false); $t->timestamps();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) $t->integer($column)->nullable();
        });
        foreach (['provinces', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->integer('order')->default(0);
                $t->string('kyu_dan')->nullable(); $t->boolean('is_active')->default(true);
            });
        }
        foreach (['cities', 'dojos'] as $name) {
            Schema::create($name, function (Blueprint $t) { $t->id(); $t->string('name'); });
        }
        (require database_path('migrations/2026_11_18_000002_add_privacy_center.php'))->up();
    }

    private function user(string $name, string $role = 'member'): User
    {
        return User::create(['name' => $name, 'email' => $name.'@example.test', 'role' => $role]);
    }

    public function test_public_policy_and_private_routes(): void
    {
        $this->get(route('privacy.policy'))->assertOk()->assertSee('+62 817-6687-726');
        $this->get(route('privacy.index'))->assertRedirect(route('login'));
        $this->get(route('privacy.admin'))->assertRedirect(route('login'));
        $this->actingAs($this->user('member'))->get(route('privacy.admin'))->assertForbidden();
    }

    public function test_consent_is_separate_recorded_and_revocable_only_by_owner(): void
    {
        $owner = $this->user('owner'); $other = $this->user('other');
        $this->assertFalse($owner->fresh()->public_directory_consent);
        $this->actingAs($owner)->put(route('privacy.consent'), ['directory_consent' => 1, 'user_id' => $other->id])->assertRedirect();
        $this->assertTrue($owner->fresh()->public_directory_consent);
        $this->assertFalse($other->fresh()->public_directory_consent);
        $this->assertFalse($owner->fresh()->is_active);
        $this->assertSame('pending', $owner->fresh()->verification_status);
        $this->put(route('privacy.consent'), ['directory_consent' => 0])->assertRedirect();
        $this->assertFalse($owner->fresh()->public_directory_consent);
        $this->assertDatabaseCount('privacy_events', 2);
        $this->withSession(['impersonation' => ['actor_id' => 99]])->put(route('privacy.consent'), ['directory_consent' => 1])->assertForbidden();
    }

    public function test_requests_are_encrypted_isolated_and_only_admin_can_respond(): void
    {
        $owner = $this->user('owner'); $other = $this->user('other');
        $this->actingAs($owner)->post(route('privacy.store'), ['kind' => 'deletion', 'details' => 'Mohon hapus data profil saya.', 'user_id' => $other->id, 'status' => 'completed'])->assertRedirect();
        $item = PrivacyRequest::firstOrFail();
        $this->assertSame($owner->id, $item->user_id);
        $this->assertSame('pending', $item->status);
        $this->assertNotSame($item->details, DB::table('privacy_requests')->value('details'));
        $this->actingAs($other)->get(route('privacy.index'))->assertOk()->assertDontSee('Mohon hapus data profil saya.');
        $this->put(route('privacy.update', $item), ['status' => 'completed', 'response' => 'Data telah ditangani.'])->assertForbidden();
        $admin = $this->user('admin', 'super_admin');
        $this->actingAs($admin)->get(route('privacy.admin'))->assertOk()->assertSee('Mohon hapus data profil saya.');
        $this->actingAs($admin)->put(route('privacy.update', $item), ['status' => 'rejected', 'response' => ''])->assertSessionHasErrors('response');
        $this->put(route('privacy.update', $item), ['status' => 'processing', 'response' => 'Permintaan sedang diperiksa.'])->assertRedirect();
        $this->put(route('privacy.update', $item), ['status' => 'completed', 'response' => 'Tindakan yang diminta telah dilaksanakan.'])->assertRedirect();
        $this->assertSame($admin->id, $item->fresh()->reviewer_id);
        $this->put(route('privacy.update', $item), ['status' => 'rejected', 'response' => 'Ubah keputusan sebelumnya.'])->assertStatus(409);
        $this->actingAs($owner)->get(route('privacy.index'))->assertOk()->assertSee('Tindakan yang diminta telah dilaksanakan.');
    }

    public function test_directory_requires_verification_and_consent_without_affecting_total(): void
    {
        $hidden = $this->user('Hidden'); $shown = $this->user('Shown'); $pending = $this->user('Pending');
        DB::table('users')->whereIn('id', [$hidden->id, $shown->id])->update(['verification_status' => 'approved']);
        DB::table('users')->whereIn('id', [$shown->id, $pending->id])->update(['public_directory_consent' => true]);
        $this->get(route('public.members'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertViewHas('members', fn ($members) => $members->pluck('id')->all() === [$shown->id]);
        $this->assertSame(2, User::members()->where('verification_status', 'approved')->count());
        $this->actingAs($shown)->put(route('privacy.consent'), ['directory_consent' => 0])->assertRedirect();
        $this->get(route('public.members'))->assertOk()
            ->assertViewHas('members', fn ($members) => $members->isEmpty());
    }
}
