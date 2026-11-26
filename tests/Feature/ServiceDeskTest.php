<?php

namespace Tests\Feature;

use App\Models\{User, Province, City};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Tests\TestCase;

class ServiceDeskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'service_test', 'database.connections.service_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Storage::fake('local');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); foreach (['name', 'email', 'role'] as $key) $t->string($key);
            $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamp('email_verified_at')->nullable();
            $t->string('registration_type')->default('karateka'); $t->string('verification_status')->default('pending'); $t->string('photo')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $key) $t->integer($key)->nullable(); $t->timestamps();
        });
        foreach (['provinces', 'cities', 'dojos', 'officials'] as $name) Schema::create($name, function (Blueprint $t) use ($name) {
            $t->id(); $t->string('name'); $t->string('sk_document_path')->nullable(); $t->string('sk_number')->nullable();
            if (in_array($name, ['provinces', 'cities'])) $t->string('code')->nullable()->unique();
            foreach (['province_id', 'city_id', 'user_id'] as $key) $t->integer($key)->nullable(); $t->timestamps();
        });
        Schema::create('districts', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('city_id'); $t->timestamps(); });
        Schema::create('belt_levels', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('kyu_dan')->nullable(); });
        Schema::create('exams', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('review_status')->default('draft'); $t->json('certificate_settings')->nullable(); $t->timestamps(); });
        Schema::create('exam_certificates', function (Blueprint $t) { $t->id(); $t->timestamp('sent_at')->nullable(); });
        Schema::create('access_audits', function (Blueprint $t) { $t->id(); $t->integer('actor_id')->nullable(); $t->integer('target_id')->nullable(); $t->integer('exam_id')->nullable(); $t->string('event'); $t->json('before'); $t->json('after'); $t->timestamps(); });
    }

    private function actor(string $role): User
    {
        $user = User::create(['name' => $role, 'email' => $role.'@example.test', 'role' => $role]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }

    public function test_each_service_has_a_working_workspace_and_only_relevant_actions(): void
    {
        foreach (array_keys(\App\Services\RoleAccess::SERVICE_POSITIONS) as $role) {
            $this->actingAs($this->actor($role))->get(route('admin.services.index'))->assertOk()->assertSee('Layanan Bantuan SIM-KKI');
        }
        $this->actingAs(User::where('role', 'bantuan_admin_surat_menyurat')->first())->get(route('admin.services.index'))
            ->assertSee('Administrasi sertifikat')->assertSee('Arsip dokumen SK')->assertDontSee('Manajemen akun &amp; kewenangan', false)->assertDontSee('Sinkronkan kode wilayah');
        $this->actingAs(User::where('role', 'adaptasi_database_digitalisasi')->first())->get(route('admin.services.index'))->assertSee('Sinkronkan kode wilayah')->assertSee('Ekspor master wilayah');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.services.archives'))->assertForbidden();
        $this->actingAs($this->actor('member'))->get(route('admin.services.index'))->assertForbidden();
    }

    public function test_digitalisation_sync_and_export_preserve_links_and_are_audited(): void
    {
        $province = Province::create(['name' => 'Bali']);
        $city = City::create(['name' => 'KOTA DENPASAR', 'province_id' => $province->id]);
        $actor = $this->actor('adaptasi_database_digitalisasi');
        $this->actingAs($actor)->post(route('admin.services.sync-regions'))->assertRedirect(route('admin.services.index'));
        $this->assertSame('51.71', $city->fresh()->code);
        $this->assertSame($province->id, $city->fresh()->province_id);
        $this->assertDatabaseHas('access_audits', ['actor_id' => $actor->id, 'event' => 'regions.codes.synced']);
        $response = $this->get(route('admin.services.export-regions'))->assertOk();
        $this->assertStringContainsString('51.71', $response->streamedContent());
        $this->assertDatabaseHas('access_audits', ['event' => 'regions.exported']);
        $this->actingAs($this->actor('bantuan_admin_surat_menyurat'))->post(route('admin.services.sync-regions'))->assertForbidden();
        $this->get(route('admin.services.export-regions'))->assertForbidden();
    }

    public function test_correspondence_can_read_sk_archives_but_not_modify_users_or_verify_members(): void
    {
        Storage::disk('local')->put('sk-documents/officials/test.pdf', 'SK Test');
        $id = DB::table('officials')->insertGetId(['name' => 'Pengurus Arsip', 'sk_number' => 'SK/001', 'sk_document_path' => 'sk-documents/officials/test.pdf']);
        $this->actingAs($this->actor('bantuan_admin_surat_menyurat'))->get(route('admin.services.archives'))->assertOk()->assertSee('Pengurus Arsip');
        $this->get(route('admin.sk-documents.show', ['entity' => 'official', 'id' => $id]))->assertOk();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.verifications.index'))->assertForbidden();
        $this->actingAs($this->actor('koordinator_nasional_sim_kki'))->get(route('admin.access-audits.index'))->assertOk();
        $this->get(route('admin.services.archives'))->assertForbidden();
    }
}
