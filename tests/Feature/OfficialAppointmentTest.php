<?php

namespace Tests\Feature;

use App\Models\{User, Official};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB, Storage};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OfficialAppointmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'appointment_test', 'database.connections.appointment_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Storage::fake('local');
        Storage::fake('public');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('role');
            $t->json('positions')->nullable(); $t->json('roles')->nullable();
            $t->string('registration_type')->default('official'); $t->string('whatsapp')->nullable(); $t->string('requested_position')->nullable();
            $t->boolean('is_active')->default(false); $t->timestamp('email_verified_at')->nullable();
            $t->string('verification_status')->default('pending'); $t->string('photo')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id'] as $column) $t->integer($column)->nullable();
            $t->timestamps();
        });
        Schema::create('provinces', function (Blueprint $t) { $t->id(); $t->string('name')->default('Provinsi'); });
        Schema::create('dojos', function (Blueprint $t) { $t->id(); $t->integer('city_id')->nullable(); $t->string('name'); });
        Schema::create('cities', function (Blueprint $t) { $t->id(); $t->integer('province_id'); $t->string('name')->default('Kota'); });
        Schema::create('officials', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('position'); $t->string('level');
            $t->integer('province_id')->nullable(); $t->integer('city_id')->nullable();
            $t->string('phone_number')->nullable(); $t->string('sk_number');
            $t->date('sk_expiry_date'); $t->string('sk_document_path')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_11_15_000002_link_officials_to_users.php'))->up();
        (require database_path('migrations/2026_11_15_000003_add_official_numbers.php'))->up();
        (require database_path('migrations/2026_11_26_000001_update_official_registration_numbers.php'))->up();
        (require database_path('migrations/2026_11_26_000002_add_official_photo_path.php'))->up();
        Schema::create('access_audits', function (Blueprint $t) {
            $t->id(); $t->integer('actor_id')->nullable(); $t->integer('target_id')->nullable();
            $t->integer('exam_id')->nullable(); $t->string('event'); $t->json('before'); $t->json('after'); $t->timestamps();
        });
        DB::table('provinces')->insert([['id' => 1], ['id' => 2]]);
        DB::table('cities')->insert([['id' => 1, 'province_id' => 1], ['id' => 2, 'province_id' => 2]]);
    }

    private function account(string $role, int $province = 1): User
    {
        $user = User::create(['name' => 'Nama '.$role, 'email' => uniqid().'@example.com', 'role' => $role, 'positions' => [$role], 'roles' => [], 'province_id' => $province]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['level' => 'pengcab', 'position' => 'Sekretaris', 'province_id' => 1, 'city_id' => 1, 'sk_number' => 'SK/001', 'sk_expiry_date' => now()->addYear()->toDateString()], $extra);
    }

    public function test_national_official_can_be_created_and_appointed_without_a_region(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $this->actingAs($actor)->get(route('admin.officials.create'))->assertOk()->assertSee('Pengurus Pusat');
        $data = $this->payload(['level' => 'pusat', 'name' => 'Pengurus Nasional', 'province_id' => null, 'city_id' => null]);
        $this->post(route('admin.officials.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('officials', ['name' => 'Pengurus Nasional', 'level' => 'pusat', 'province_id' => null, 'city_id' => null]);
        $this->post(route('admin.users.appoint', $user), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'pb', 'province_id' => null, 'city_id' => null, 'is_active' => 1]);
        $this->actingAs($this->account('pengprov'))->post(route('admin.officials.store'), $data)->assertSessionHasErrors('level');
    }

    public function test_appointment_activates_region_links_record_and_updates_without_duplicates(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $this->payload(['sk_document' => UploadedFile::fake()->create('sk.pdf', 20, 'application/pdf')]))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'pengcab', 'is_active' => 1, 'province_id' => 1, 'city_id' => 1]);
        $official = Official::where('user_id', $user->id)->firstOrFail();
        Storage::disk('local')->assertExists($official->sk_document_path);
        $path = $official->sk_document_path;
        $this->post(route('admin.users.appoint', $user), $this->payload(['position' => 'Bendahara']))->assertRedirect();
        $this->assertDatabaseCount('officials', 1);
        $this->assertSame('Bendahara', $official->fresh()->position);
        $this->assertSame($path, $official->fresh()->sk_document_path);
        $this->assertDatabaseCount('access_audits', 2);
    }

    public function test_provincial_admin_cannot_assign_other_region_or_provincial_role(): void
    {
        $actor = $this->account('pengprov'); $user = $this->account('member');
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $this->payload(['province_id' => 2, 'city_id' => 2]))->assertForbidden();
        $this->post(route('admin.users.appoint', $user), $this->payload(['level' => 'provinsi']))->assertSessionHasErrors('level');
        $other = $this->account('member', 2);
        $this->post(route('admin.users.appoint', $other), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('officials', 0);
        $this->assertSame('member', $user->fresh()->role);
    }

    public function test_invalid_city_expired_sk_and_unprivileged_actor_are_rejected(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $this->payload(['city_id' => 2]))->assertSessionHasErrors('city_id');
        $this->post(route('admin.users.appoint', $user), $this->payload(['sk_expiry_date' => now()->subDay()->toDateString()]))->assertSessionHasErrors('sk_expiry_date');
        $this->actingAs($user)->post(route('admin.users.appoint', $actor), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('officials', 0);
    }

    public function test_verification_menu_lists_pending_and_appointed_officials_separately(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $user->update(['name' => 'Calon Pengurus Khusus']);
        $karateka = $this->account('member');
        $karateka->update(['registration_type' => 'karateka', 'name' => 'Karateka Biasa']);
        $this->actingAs($actor)->get(route('admin.official-verifications.index'))
            ->assertOk()->assertSee('Verifikasi Pengurus')->assertSee('Calon Pengurus Khusus')->assertDontSee('Karateka Biasa');
        $this->get(route('admin.official-verifications.show', $user))->assertRedirect(route('admin.officials.create', ['user_id' => $user->id]));
        $this->get(route('admin.officials.create', ['user_id' => $user->id]))->assertOk()->assertSee('Calon Pengurus Khusus')->assertSee('Simpan &amp; Tetapkan Pengurus', false);
        $this->post(route('admin.users.appoint', $user), $this->payload(['return_to' => 'official-verifications']))
            ->assertRedirect(route('admin.official-verifications.show', $user));
        $this->get(route('admin.official-verifications.index'))->assertDontSee('Calon Pengurus Khusus');
        $this->get(route('admin.official-verifications.index', ['status' => 'appointed']))->assertSee('Calon Pengurus Khusus');
    }

    public function test_verification_pages_restrict_provincial_scope_and_member_access(): void
    {
        $actor = $this->account('pengprov'); $outside = $this->account('member', 2);
        $outside->update(['name' => 'Pengurus Luar Wilayah']);
        $this->actingAs($actor)->get(route('admin.official-verifications.index'))->assertOk()->assertDontSee('Pengurus Luar Wilayah');
        $this->get(route('admin.officials.create', ['user_id' => $outside->id]))->assertNotFound();
        $this->get(route('admin.official-verifications.show', $outside))->assertForbidden();
        $this->actingAs($outside)->get(route('admin.official-verifications.index'))->assertForbidden();
    }

    public function test_existing_official_form_saves_the_proposal_and_account_together(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $user->update(['requested_position' => 'Sekretaris Bidang Khusus', 'whatsapp' => '081234567890']);
        $this->actingAs($actor)->get(route('admin.officials.create', ['user_id' => $user->id]))
            ->assertOk()->assertSee('Sekretaris Bidang Khusus')->assertSee('081234567890');
        $this->post(route('admin.users.appoint', $user), $this->payload(['name' => 'Nama dan Gelar', 'phone_number' => '081234567890', 'return_to' => 'officials']))
            ->assertRedirect(route('admin.officials.index'));
        $this->assertDatabaseHas('officials', ['user_id' => $user->id, 'name' => 'Nama dan Gelar', 'phone_number' => '081234567890']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama dan Gelar', 'role' => 'pengcab', 'is_active' => 1]);
    }

    public function test_official_badge_counts_only_pending_applications_in_the_admin_region(): void
    {
        $actor = $this->account('pengprov');
        $actor->update(['registration_type' => 'karateka']);
        $user = $this->account('member');
        $this->account('member', 2);
        $this->actingAs($actor)->get(route('admin.official-verifications.index'))
            ->assertOk()->assertSee('aria-label="1 pengajuan pengurus menunggu verifikasi"', false);
        $this->post(route('admin.users.appoint', $user), $this->payload())->assertRedirect();
        $this->get(route('admin.official-verifications.index'))
            ->assertOk()->assertDontSee('pengajuan pengurus menunggu verifikasi');
        $this->assertSame(0, app(\App\Services\OfficialReview::class)->pendingCount($actor));
        $national = $this->account('pb');
        $national->update(['registration_type' => 'karateka']);
        $this->assertSame(1, app(\App\Services\OfficialReview::class)->pendingCount($national));
    }

    public function test_official_id_is_issued_once_and_can_be_managed_only_by_super_admin(): void
    {
        DB::table('provinces')->where('id', 1)->update(['name' => 'Sumatera Utara']);
        $actor = $this->account('pb'); $user = $this->account('member');
        $this->assertNull($user->official);
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $this->payload())->assertRedirect();
        $official = Official::where('user_id', $user->id)->firstOrFail();
        $original = 'PGR-SMT-UNK-000001-'.now()->addYear()->format('Y');
        $this->assertSame($original, $official->official_number);
        $this->post(route('admin.users.appoint', $user), $this->payload(['province_id' => 2, 'city_id' => 2]))->assertRedirect();
        $this->assertSame($official->registration_sequence, $official->fresh()->registration_sequence);
        $this->assertNotSame($original, $official->fresh()->official_number);
        $original = $official->fresh()->official_number;
        $data = ['mode' => 'manual', 'number' => 'pgr-smt-009999', 'expected_number' => $original, 'reason' => 'Koreksi arsip'];
        $this->put(route('admin.official-numbers.update', $official), $data)->assertForbidden();
        $admin = $this->account('super_admin');
        $this->actingAs($admin)->put(route('admin.official-numbers.update', $official), $data)->assertRedirect();
        $this->assertSame('PGR-SMT-009999', $official->fresh()->official_number);
        $this->assertDatabaseHas('access_audits', ['event' => 'official.number.updated', 'actor_id' => $admin->id]);
        $this->put(route('admin.official-numbers.update', $official), $data)->assertSessionHasErrors('number');
        $this->get(route('admin.official-numbers.index'))->assertOk()->assertSee('PGR-SMT-009999');
        $this->get(route('admin.official-numbers.edit', $official))->assertOk()->assertSee('Koreksi arsip');
    }

    public function test_duplicate_official_numbers_are_rejected_without_changing_the_number_or_audit(): void
    {
        $actor = $this->account('pb'); $one = $this->account('member'); $two = $this->account('member');
        $this->actingAs($actor)->post(route('admin.users.appoint', $one), $this->payload())->assertRedirect();
        $this->post(route('admin.users.appoint', $two), $this->payload())->assertRedirect();
        $first = Official::where('user_id', $one->id)->firstOrFail();
        $second = Official::where('user_id', $two->id)->firstOrFail();
        $original = $second->official_number;
        $admin = $this->account('super_admin');
        $this->actingAs($admin)->put(route('admin.official-numbers.update', $second), [
            'mode' => 'manual', 'number' => $first->official_number, 'expected_number' => $original, 'reason' => 'Duplikat',
        ])->assertSessionHasErrors('number');
        $this->assertSame($original, $second->fresh()->official_number);
        $this->assertDatabaseMissing('access_audits', ['event' => 'official.number.updated']);
    }
    public function test_central_assignment_can_be_revised_and_the_id_tracks_the_sk_year(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $data = $this->payload(['level' => 'pusat', 'province_id' => null, 'city_id' => null, 'sk_expiry_date' => '2030-12-31']);
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $data)->assertSessionHasNoErrors();
        $official = $user->official()->firstOrFail();
        $this->assertSame('PGR-PP-000001-2030', $official->official_number);
        $this->get(route('admin.officials.create', ['user_id' => $user->id]))->assertOk()->assertSee('Pengurus Pusat')->assertSee('2030-12-31');
        Schema::create('belt_levels', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); });
        $this->get(route('admin.users.edit', $user))->assertOk()->assertSee('Perbarui penetapan pengurus');
        $this->put(route('admin.officials.update', $official), array_replace($data, ['position' => 'Ketua Bidang', 'sk_expiry_date' => '2032-12-31']))->assertSessionHasNoErrors();
        $this->assertSame('PGR-PP-000001-2032', $official->fresh()->official_number);
        $this->assertSame('Ketua Bidang', $official->fresh()->position);
    }

    public function test_deleted_official_sequence_is_reserved_and_manual_number_survives_revision(): void
    {
        $actor = $this->account('pb');
        $data = $this->payload(['name' => 'Pengurus Manual', 'level' => 'provinsi', 'city_id' => null, 'sk_expiry_date' => '2030-12-31']);
        $this->actingAs($actor)->post(route('admin.officials.store'), $data)->assertSessionHasNoErrors();
        $first = Official::firstOrFail();
        $this->assertStringEndsWith('-000001-2030', $first->official_number);
        $this->delete(route('admin.officials.destroy', $first))->assertRedirect();
        $this->post(route('admin.officials.store'), $data)->assertSessionHasNoErrors();
        $second = Official::firstOrFail();
        $this->assertSame(2, $second->registration_sequence);
        $second->forceFill(['official_number' => 'PGR-ARSIP-001', 'official_number_manual' => true])->saveQuietly();
        $this->put(route('admin.officials.update', $second), array_replace($data, ['sk_expiry_date' => '2031-01-01']))->assertSessionHasNoErrors();
        $this->assertSame('PGR-ARSIP-001', $second->fresh()->official_number);
    }

    public function test_official_verification_permissions_are_scoped_to_their_levels_and_regions(): void
    {
        $province = $this->account('verifikator_pengurus_provinsi');
        $branch = $this->account('verifikator_pengurus_pengcab'); $branch->update(['city_id' => 1]);
        $central = $this->account('verifikator_pengurus_pusat');
        $user = $this->account('member'); $user->update(['city_id' => 1]);
        $outside = $this->account('member', 2); $outside->update(['city_id' => 2]);
        $this->actingAs($branch)->get(route('dashboard'))->assertRedirect(route('admin.official-verifications.index', [], false));
        $this->get(route('admin.officials.create', ['user_id' => $user->id]))->assertOk();
        $this->post(route('admin.users.appoint', $user), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('admin.users.appoint', $outside), $this->payload(['province_id' => 2, 'city_id' => 2]))->assertForbidden();
        $this->post(route('admin.users.appoint', $user), $this->payload(['level' => 'provinsi']))->assertSessionHasErrors('level');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($province)->post(route('admin.users.appoint', $user), $this->payload(['level' => 'provinsi', 'city_id' => null]))->assertSessionHasNoErrors();
        $this->post(route('admin.users.appoint', $user), $this->payload(['level' => 'pusat']))->assertSessionHasErrors('level');
        $this->actingAs($central)->post(route('admin.users.appoint', $outside), $this->payload(['level' => 'pusat', 'province_id' => null, 'city_id' => null]))->assertSessionHasNoErrors();
        $this->assertSame('pb', $outside->fresh()->role);
        $this->actingAs($branch)->get(route('admin.officials.edit', $outside->official))->assertForbidden();
    }

    public function test_number_migration_updates_automatic_ids_and_preserves_manual_ids(): void
    {
        $migration = require database_path('migrations/2026_11_26_000001_update_official_registration_numbers.php');
        $migration->down();
        DB::table('officials')->insert([
            ['name' => 'Pusat Lama', 'position' => 'Sekretaris', 'level' => 'pusat', 'sk_number' => 'SK/PP', 'sk_expiry_date' => '2030-12-31', 'official_number' => 'PGR-UNK-000001', 'official_number_manual' => false],
            ['name' => 'Nomor Manual', 'position' => 'Sekretaris', 'level' => 'pusat', 'sk_number' => 'SK/PP', 'sk_expiry_date' => '2030-12-31', 'official_number' => 'PGR-ARSIP-123', 'official_number_manual' => true],
        ]);
        $migration->up();
        $this->assertDatabaseHas('officials', ['name' => 'Pusat Lama', 'official_number' => 'PGR-PP-000001-2030', 'registration_sequence' => 1]);
        $this->assertDatabaseHas('officials', ['name' => 'Nomor Manual', 'official_number' => 'PGR-ARSIP-123', 'registration_sequence' => 2]);
        $this->assertDatabaseHas('registration_counters', ['type' => 'official', 'last_number' => 2]);
    }

    public function test_manual_official_photo_is_uploaded_displayed_replaced_and_validated(): void
    {
        $this->actingAs($this->account('pb'));
        $data = $this->payload(['name' => 'Pengurus Berfoto', 'photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]);
        $this->post(route('admin.officials.store'), $data)->assertSessionHasNoErrors();
        $official = Official::firstOrFail(); $old = $official->photo_path;
        Storage::disk('public')->assertExists($old);
        $this->get(route('admin.officials.index'))->assertOk()->assertSee(url('storage/'.$old));
        $this->get(route('admin.officials.edit', $official))->assertOk()->assertSee('name="photo"', false)->assertSee(url('storage/'.$old));
        $this->put(route('admin.officials.update', $official), array_replace($data, ['photo' => UploadedFile::fake()->image('baru.png')]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $new = $official->fresh()->photo_path;
        Storage::disk('public')->assertExists($new);
        foreach ([UploadedFile::fake()->create('dokumen.pdf', 20), UploadedFile::fake()->image('besar.jpg')->size(2049)] as $invalid) {
            $this->put(route('admin.officials.update', $official), array_replace($data, ['photo' => $invalid]))->assertSessionHasErrors('photo');
            $this->assertSame($new, $official->fresh()->photo_path);
        }
        $this->delete(route('admin.officials.destroy', $official))->assertRedirect();
        Storage::disk('public')->assertMissing($new);
    }

    public function test_linked_official_uses_and_updates_the_account_photo_without_a_duplicate_copy(): void
    {
        $actor = $this->account('pb'); $user = $this->account('member');
        $user->photo = UploadedFile::fake()->image('akun.jpg')->store('member-photos/'.$user->id, 'public'); $user->save();
        $old = $user->photo;
        $this->actingAs($actor)->post(route('admin.users.appoint', $user), $this->payload())->assertSessionHasNoErrors();
        $official = $user->official()->firstOrFail();
        $this->assertSame(url('storage/'.$old), $official->photo_url);
        $this->get(route('admin.officials.create', ['user_id' => $user->id]))->assertOk()->assertSee(url('storage/'.$old));
        $this->post(route('admin.users.appoint', $user), $this->payload(['photo' => UploadedFile::fake()->image('pengurus.png')]))->assertSessionHasNoErrors();
        $new = $user->fresh()->photo;
        $this->assertSame(url('storage/'.$new), $official->fresh()->photo_url);
        $this->assertNull($official->fresh()->photo_path);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->get(route('admin.officials.index'))->assertOk()->assertSee(url('storage/'.$new));
        $this->post(route('admin.users.appoint', $user), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($new, $user->fresh()->photo);
        $this->delete(route('admin.officials.destroy', $official))->assertRedirect();
        Storage::disk('public')->assertExists($new);
    }

}
