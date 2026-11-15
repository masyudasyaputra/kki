<?php

namespace Tests\Feature;

use App\Models\{User, OfficialQualification};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Schema, DB, Storage};
use Tests\TestCase;

class QualificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'qualification_test', 'database.connections.qualification_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Storage::fake('local');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('role');
            $t->string('registration_type')->default('karateka'); $t->string('verification_status')->default('pending');
            $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->integer('province_id')->nullable(); $t->integer('city_id')->nullable(); $t->integer('belt_level_id')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamp('email_verified_at')->nullable(); $t->timestamps();
        });
        Schema::create('provinces', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('cities', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('province_id'); });
        DB::table('provinces')->insert([['id' => 1, 'name' => 'Sumatera Utara'], ['id' => 2, 'name' => 'Jawa Barat']]);
        DB::table('cities')->insert([['id' => 1, 'name' => 'Medan', 'province_id' => 1], ['id' => 2, 'name' => 'Bandung', 'province_id' => 2]]);
        (require database_path('migrations/2026_11_15_000004_create_official_qualifications.php'))->up();
        Schema::create('access_audits', function (Blueprint $t) {
            $t->id(); $t->integer('actor_id')->nullable(); $t->integer('target_id')->nullable(); $t->integer('exam_id')->nullable();
            $t->string('event'); $t->json('before'); $t->json('after'); $t->timestamps();
        });
    }

    private function account(string $role = 'member', int $region = 1): User
    {
        $user = User::create(['name' => 'Akun '.uniqid(), 'email' => uniqid().'@example.test', 'role' => $role, 'roles' => [], 'positions' => [$role], 'province_id' => $region, 'city_id' => $region]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['kind' => 'both', 'license_level' => 'Nasional', 'certificate_number' => 'WJ/001', 'issuer' => 'Penerbit', 'issued_at' => today()->subYear()->toDateString(), 'expires_at' => today()->addYear()->toDateString(), 'document' => UploadedFile::fake()->create('sertifikat.pdf', 20, 'application/pdf')], $extra);
    }

    private function submit(User $user): OfficialQualification
    {
        $this->actingAs($user)->post(route('qualifications.store'), $this->payload())->assertRedirect(route('qualifications.index'));
        return $user->qualifications()->latest('id')->firstOrFail();
    }

    public function test_karateka_submits_without_new_account_or_privileged_roles_and_document_is_private(): void
    {
        $user = $this->account();
        $this->actingAs($user)->get(route('qualifications.index'))->assertOk()->assertSee('Ajukan kompetensi');
        $this->post(route('qualifications.store'), $this->payload(['status' => 'approved', 'roles' => ['pb']]))->assertRedirect();
        $record = $user->qualifications()->firstOrFail();
        $this->assertSame('pending', $record->status);
        $this->assertSame('member', $user->fresh()->role);
        $this->assertDatabaseCount('users', 1);
        Storage::disk('local')->assertExists($record->document_path);
        $this->get(route('qualifications.document', $record))->assertOk();
        $this->actingAs($this->account())->get(route('qualifications.document', $record))->assertForbidden();
        $this->actingAs($this->account('pengprov', 2))->get(route('qualifications.document', $record))->assertForbidden();
    }

    public function test_regional_review_approves_competence_without_changing_account_access(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $admin = $this->account('pengprov');
        $this->actingAs($admin)->get(route('admin.qualifications.index'))->assertOk()->assertSee('WJ/001');
        $this->get(route('admin.qualifications.show', $record))->assertOk();
        $this->post(route('admin.qualifications.store', $record), ['status' => 'approved'])->assertRedirect();
        $this->assertTrue($record->fresh()->is_valid);
        $this->assertSame('member', $user->fresh()->role);
        $this->assertFalse($user->fresh()->hasRole(['pb', 'penguji']));
        $this->assertDatabaseHas('access_audits', ['event' => 'qualification.reviewed', 'actor_id' => $admin->id]);
        $this->get(route('admin.qualifications.index', ['status' => 'approved']))->assertSee('WJ/001');
        $this->post(route('admin.qualifications.store', $record), ['status' => 'approved'])->assertSessionHasErrors('status');
    }

    public function test_review_blocks_other_regions_members_and_self_approval(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $this->actingAs($this->account('pengcab', 2))->post(route('admin.qualifications.store', $record), ['status' => 'approved'])->assertNotFound();
        $this->actingAs($user)->post(route('admin.qualifications.store', $record), ['status' => 'approved'])->assertForbidden();
        $user->update(['role' => 'pengprov', 'belt_level_id' => 1]);
        $this->actingAs($user)->post(route('admin.qualifications.store', $record), ['status' => 'approved'])->assertForbidden();
        $this->assertSame('pending', $record->fresh()->status);
    }

    public function test_duplicate_pending_application_is_blocked_and_revision_can_be_resubmitted(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $this->post(route('qualifications.store'), $this->payload())->assertSessionHasErrors('kind');
        $this->assertDatabaseCount('official_qualifications', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->actingAs($this->account('pb'))->post(route('admin.qualifications.store', $record), ['status' => 'revision'])->assertSessionHasErrors('notes');
        $this->post(route('admin.qualifications.store', $record), ['status' => 'revision', 'notes' => 'Perjelas dokumen'])->assertRedirect();
        $this->actingAs($user)->get(route('qualifications.index'))->assertSee('Perjelas dokumen');
        $this->submit($user);
        $this->assertDatabaseCount('official_qualifications', 2);
        $this->assertSame('revision', $record->fresh()->status);
    }

    public function test_expired_license_is_not_active_and_non_karateka_cannot_submit(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $record->update(['status' => 'approved', 'expires_at' => today()->subDay()]);
        $this->assertFalse($record->fresh()->is_valid);
        $this->actingAs($this->account('pb'))->get(route('admin.qualifications.index', ['status' => 'approved']))->assertDontSee('WJ/001');
        $this->get(route('admin.qualifications.index', ['status' => 'expired']))->assertSee('WJ/001');
        $user->update(['registration_type' => 'official']);
        $this->actingAs($user)->post(route('qualifications.store'), $this->payload())->assertForbidden();
    }

    public function test_profile_badge_only_shows_approved_unexpired_competence(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $html = view('profile.partials.qualifications', ['user' => $user])->render();
        $this->assertStringNotContainsString('Wasit &amp; Juri Terverifikasi', $html);
        $record->update(['status' => 'approved']);
        $html = view('profile.partials.qualifications', ['user' => $user])->render();
        $this->assertStringContainsString('Wasit &amp; Juri Terverifikasi', $html);
        $record->update(['expires_at' => today()->subDay()]);
        $html = view('profile.partials.qualifications', ['user' => $user])->render();
        $this->assertStringNotContainsString('Wasit &amp; Juri Terverifikasi', $html);
    }

    public function test_dashboard_and_card_badges_combine_only_current_approved_licenses(): void
    {
        $user = $this->account(); $record = $this->submit($user);
        $badges = app(\App\Services\QualificationBadges::class);
        $this->assertSame(['qualificationBadges' => [], 'cardQualificationLabel' => null], $badges->forUser($user));
        $record->update(['status' => 'approved', 'kind' => 'referee', 'expires_at' => today()]);
        $presentation = $badges->forUser($user);
        $this->assertSame('WASIT', $presentation['cardQualificationLabel']);
        $this->assertSame(['Wasit Terverifikasi · Nasional'], $presentation['qualificationBadges']);
        $overview = view('member.overview', ['user' => $user, 'verification' => 'Menunggu pemeriksaan', 'latestVerification' => null,
            'summary' => ['amount' => 0, 'pending' => 0, 'exams' => 0, 'certificates' => 0]] + $presentation)->render();
        $this->assertStringContainsString('Wasit Terverifikasi · Nasional', $overview);
        $this->assertStringContainsString('kd-qualification-badges', $overview);
        $copy = $record->replicate();
        $copy->kind = 'judge'; $copy->expires_at = null; $copy->save();
        $presentation = $badges->forUser($user);
        $this->assertSame('WASIT & JURI', $presentation['cardQualificationLabel']);
        $html = view('member.card', ['user' => $user, 'qrPngBase64' => base64_encode('<svg/>')] + $presentation)->render();
        $this->assertStringContainsString('kki-id-qualification', $html);
        $this->assertStringContainsString('WASIT &amp; JURI', $html);
        $this->assertStringContainsString('&quot;qualification&quot;', $html);
        $record->update(['expires_at' => today()->subDay()]);
        $this->assertSame('JURI', $badges->forUser($user)['cardQualificationLabel']);
        $copy->update(['status' => 'revision']);
        $presentation = $badges->forUser($user);
        $this->assertNull($presentation['cardQualificationLabel']);
        $html = view('member.card-face', ['side' => 'front', 'user' => $user] + $presentation)->render();
        $this->assertStringNotContainsString('kki-id-qualification', $html);
    }
}
