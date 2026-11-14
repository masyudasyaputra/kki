<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KaratekaWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'karateka_test', 'database.connections.karateka_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->json('roles')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('whatsapp')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('first_coach')->nullable();
            $table->string('district')->nullable();
            $table->string('photo')->nullable();
            $table->text('address')->nullable();
            $table->string('verification_status')->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $field) {
                $table->integer($field)->nullable();
            }
            $table->timestamps();
        });
        foreach (['provinces', 'cities', 'districts', 'dojos', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('name');
                if ($name === 'cities') $table->integer('province_id');
                if (in_array($name, ['districts', 'dojos'])) $table->integer('city_id');
                if ($name === 'belt_levels') {
                    $table->boolean('is_active')->default(true);
                    $table->integer('order')->default(1);
                }
            });
        }
        Schema::create('membership_verifications', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id');
            $table->integer('reviewer_id')->nullable();
            $table->string('reviewer_level');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('exam_id')->nullable();
            $table->integer('target_belt_id')->nullable();
        });
        Schema::create('exam_certificates', function (Blueprint $table) {
            $table->id();
            $table->integer('exam_participant_id');
            $table->string('number');
            $table->json('snapshot');
            $table->timestamp('sent_at')->nullable();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('invoice_number');
            $table->string('type')->default('iuran');
            $table->string('status')->default('pending');
            $table->integer('amount')->default(100000);
            $table->timestamps();
        });
    }

    public function test_member_pages_render_for_a_new_karateka(): void
    {
        $member = User::create(['name' => 'Karateka Baru', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member']);
        $this->actingAs($member);
        foreach (['dashboard', 'card', 'exams', 'payments', 'certificates'] as $section) {
            $this->get(route('member.'.$section))->assertOk()->assertSee('Ruang Karateka')->assertSee('Profil Saya');
        }
        $this->get(route('member.dashboard'))->assertSee('Dojo belum dipilih')->assertSee('Menunggu pemeriksaan');
    }

    public function test_member_lists_only_show_owned_records_and_delivered_certificates(): void
    {
        $member = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member']);
        DB::table('payments')->insert([
            ['user_id' => $member->id, 'invoice_number' => 'OWN-INVOICE'],
            ['user_id' => 999, 'invoice_number' => 'PRIVATE-INVOICE'],
        ]);
        DB::table('exam_participants')->insert([['id' => 1, 'user_id' => $member->id], ['id' => 2, 'user_id' => 999]]);
        DB::table('exam_certificates')->insert([
            ['exam_participant_id' => 1, 'number' => 'OWN-CERT', 'snapshot' => '{}', 'sent_at' => now()],
            ['exam_participant_id' => 2, 'number' => 'PRIVATE-CERT', 'snapshot' => '{}', 'sent_at' => now()],
            ['exam_participant_id' => 1, 'number' => 'DRAFT-CERT', 'snapshot' => '{}', 'sent_at' => null],
        ]);
        $this->actingAs($member)->get(route('member.payments'))->assertOk()->assertSee('OWN-INVOICE')->assertDontSee('PRIVATE-INVOICE');
        $this->get(route('member.certificates'))->assertOk()->assertSee('OWN-CERT')->assertDontSee('PRIVATE-CERT')->assertDontSee('DRAFT-CERT');
        $this->get(route('member.exams'))->assertOk()->assertViewHas('myExams', fn ($exams) => $exams->total() === 1);
    }

    public function test_dashboard_uses_the_members_belt_color_and_gold_only_for_striped_belts(): void
    {
        Schema::table('belt_levels', fn (Blueprint $table) => $table->string('kyu_dan')->nullable());
        $member = User::create(['name' => 'Karateka', 'email' => 'belt@example.test', 'password' => 'password', 'role' => 'member']);
        foreach ([['Hitam', 'DAN IV', '#111214', false], ['Putih', 'Kyu 7', '#f5f3ee', false], ['Kuning', 'Kyu 6', '#e7b914', false], ['Biru Strip', 'Kyu 4', '#163d80', true], ['Coklat Strip', 'Kyu 2', '#583621', true]] as $index => [$name, $rank, $color, $striped]) {
            DB::table('belt_levels')->insert(['id' => $index + 1, 'name' => $name, 'kyu_dan' => $rank]);
            DB::table('users')->where('id', $member->id)->update(['belt_level_id' => $index + 1]);
            $response = $this->actingAs($member->fresh())->get(route('member.dashboard'));
            $response->assertOk()->assertSee('--rank-base:'.$color, false)->assertSee('<h2>'.$name.'</h2>', false)->assertSee($rank)->assertDontSee('<svg class="kd-current-belt"', false);
            if ($striped) $response->assertSee('Strip emas')->assertSee('class="kd-belt-stripe"', false);
            else $response->assertDontSee('class="kd-belt-stripe"', false);
        }
    }

    public function test_guests_cannot_open_the_member_workspace(): void
    {
        foreach (['dashboard', 'card', 'exams', 'payments', 'certificates'] as $section) {
            $this->get(route('member.'.$section))->assertRedirect(route('login'));
        }
    }

    public function test_profile_shows_existing_registration_data_and_member_labels(): void
    {
        $member = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member', 'whatsapp' => '081234567890', 'parent_name' => 'Nama Wali', 'first_coach' => 'Sensei Pertama', 'address' => 'Jalan Latihan 10']);
        $this->actingAs($member)->get(route('profile.edit'))->assertOk()
            ->assertSee('Identitas & kontak', false)->assertSee('081234567890')->assertSee('Nama Wali')
            ->assertSee('Sensei Pertama')->assertSee('Jalan Latihan 10')->assertDontSee('Manajemen Profil Pengurus');
    }

    public function test_member_can_complete_profile_and_changes_require_review(): void
    {
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Sumatera Utara']);
        DB::table('cities')->insert(['id' => 1, 'name' => 'Medan', 'province_id' => 1]);
        DB::table('dojos')->insert(['id' => 1, 'name' => 'Dojo Medan', 'city_id' => 1]);
        DB::table('belt_levels')->insert(['id' => 1, 'name' => 'Putih']);
        $member = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member']);
        DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved']);
        $this->actingAs($member->fresh())->patch(route('profile.update'), [
            'name' => 'Karateka Lengkap', 'email' => 'MEMBER@example.test', 'whatsapp' => '+62 812-3456-7890',
            'parent_name' => 'Nama Wali', 'address' => 'Jalan Latihan 10', 'first_coach' => 'Sensei Pertama',
            'province_id' => 1, 'city_id' => 1, 'dojo_id' => 1, 'district' => 'Medan Tuntungan', 'belt_level_id' => 1,
            'role' => 'super_admin', 'is_active' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
        $member->refresh();
        $this->assertSame('081234567890', $member->whatsapp);
        $this->assertSame('member@example.test', $member->email);
        $this->assertSame('pending', $member->verification_status);
        $this->assertSame('member', $member->role);
        $this->assertFalse($member->is_active);
        $this->assertSame('Jalan Latihan 10', $member->address);
        $this->assertEquals(1, $member->dojo_id);
    }

    public function test_profile_rejects_dojo_from_another_city_and_duplicate_phone(): void
    {
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Sumatera Utara']);
        DB::table('cities')->insert(['id' => 1, 'name' => 'Medan', 'province_id' => 1]);
        DB::table('dojos')->insert(['id' => 1, 'name' => 'Dojo lain', 'city_id' => 999]);
        $member = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member']);
        User::create(['name' => 'Lain', 'email' => 'other@example.test', 'password' => 'password', 'role' => 'member', 'whatsapp' => '081234567890']);
        $this->actingAs($member)->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'province_id' => 1, 'city_id' => 1, 'dojo_id' => 1, 'whatsapp' => '+62 81234567890'])
            ->assertSessionHasErrors(['dojo_id', 'whatsapp']);
        $this->assertNull($member->fresh()->dojo_id);
    }

    public function test_clearing_province_clears_dependent_profile_data(): void
    {
        $member = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'member', 'province_id' => 1, 'city_id' => 2, 'dojo_id' => 3, 'district' => 'Kecamatan']);
        $this->actingAs($member)->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'province_id' => ''])
            ->assertSessionHasNoErrors();
        $member->refresh();
        foreach (['province_id', 'city_id', 'dojo_id', 'district'] as $field) $this->assertNull($member->$field);
    }

    public function test_pasfoto_upload_is_saved_displayed_and_replaced(): void
    {
        Storage::fake('public');
        $member = User::create(['name' => 'Karateka', 'email' => 'photo@example.test', 'password' => 'password', 'role' => 'member']);
        $data = ['name' => $member->name, 'email' => $member->email];
        $this->actingAs($member)->patch(route('profile.update'), $data + ['photo' => UploadedFile::fake()->image('pasfoto.jpg', 300, 400)])
            ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
        $oldPhoto = $member->fresh()->photo;
        Storage::disk('public')->assertExists($oldPhoto);
        foreach (['profile.edit', 'member.card', 'member.dashboard'] as $route) {
            $this->get(route($route))->assertOk()->assertSee(url('storage/'.$oldPhoto));
        }
        $this->patch(route('profile.update'), $data + ['photo' => UploadedFile::fake()->image('baru.png', 300, 400)])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldPhoto);
        $newPhoto = $member->fresh()->photo;
        Storage::disk('public')->assertExists($newPhoto);
        $this->patch(route('profile.update'), $data)->assertSessionHasNoErrors();
        $this->assertSame($newPhoto, $member->fresh()->photo);
    }

    public function test_invalid_pasfoto_does_not_replace_existing_photo(): void
    {
        Storage::fake('public');
        $member = User::create(['name' => 'Karateka', 'email' => 'photo@example.test', 'password' => 'password', 'role' => 'member']);
        $member->photo = UploadedFile::fake()->image('old.jpg')->store('member-photos/'.$member->id, 'public');
        $member->save();
        $oldPhoto = $member->photo;
        $this->actingAs($member);
        foreach ([UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'), UploadedFile::fake()->image('large.jpg')->size(2049)] as $file) {
            $this->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'photo' => $file])
                ->assertSessionHasErrors('photo');
            $this->assertSame($oldPhoto, $member->fresh()->photo);
            Storage::disk('public')->assertExists($oldPhoto);
        }
    }

    public function test_admin_approval_is_visible_as_verified_on_member_pages_and_survives_unchanged_save(): void
    {
        $member = User::create(['name' => 'Karateka', 'email' => 'verified@example.test', 'password' => 'password', 'role' => 'member']);
        $admin = User::create(['name' => 'Pemeriksa', 'email' => 'admin@example.test', 'password' => 'password', 'role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.verifications.store', $member), [
            'status' => 'approved', 'checked' => \App\Services\MembershipReview::FIELDS,
            'fingerprint' => app(\App\Services\MembershipReview::class)->fingerprint($member),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('approved', $member->fresh()->verification_status);
        $this->actingAs($member->fresh());
        foreach (['member.dashboard', 'member.card', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Status verifikasi data: Terverifikasi');
        }
        $this->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email])->assertSessionHasNoErrors();
        $this->assertSame('approved', $member->fresh()->verification_status);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Status verifikasi data: Terverifikasi');
    }

    public function test_changed_member_data_displays_pending_verification_instead_of_verified(): void
    {
        $member = User::create(['name' => 'Karateka', 'email' => 'verified@example.test', 'password' => 'password', 'role' => 'member']);
        DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved']);
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Sumatera Utara']);
        $this->actingAs($member->fresh())->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'province_id' => 1])->assertSessionHasNoErrors();
        $this->actingAs($member->fresh())->get(route('member.dashboard'))->assertOk()
            ->assertSee('Status verifikasi data: Menunggu verifikasi')->assertDontSee('Status verifikasi data: Terverifikasi');
    }

    public function test_contact_identity_and_photo_changes_keep_membership_verified(): void
    {
        Storage::fake('public');
        $member = User::create(['name' => 'Karateka', 'email' => 'verified@example.test', 'password' => 'password', 'role' => 'member']);
        DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($member->fresh())->patch(route('profile.update'), [
            'name' => 'Nama Baru', 'email' => 'new@example.test', 'whatsapp' => '081234567890',
            'parent_name' => 'Wali Baru', 'address' => 'Jalan Baru', 'first_coach' => 'Pelatih Baru',
            'photo' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertSame('approved', $member->verification_status);
        $this->assertSame('Nama Baru', $member->name);
        $this->assertNull($member->email_verified_at);
        Storage::disk('public')->assertExists($member->photo);
        $this->actingAs($member)->get(route('profile.edit'))->assertOk()->assertSee('Status verifikasi data: Terverifikasi');
    }

    public function test_each_belt_region_and_dojo_change_requires_verification(): void
    {
        foreach (\App\Services\MembershipReview::REQUIRES_VERIFICATION as $field) {
            $member = User::create(['name' => 'Karateka', 'email' => $field.'@example.test', 'password' => 'password', 'role' => 'member']);
            DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved']);
            $member = $member->fresh();
            $member->$field = $field === 'district' ? 'Kecamatan Baru' : 1;
            $member->save();
            $this->assertSame('pending', $member->fresh()->verification_status, $field);
        }
    }

    public function test_province_or_central_verifier_can_approve_a_changed_members_region(): void
    {
        DB::table('provinces')->insert(['id' => 1, 'name' => 'Sumatera Utara']);
        foreach (['verifikator_pengprov', 'verifikator_anggota_pb'] as $role) {
            $member = User::create(['name' => 'Karateka', 'email' => $role.'member@example.test', 'password' => 'password', 'role' => 'member']);
            DB::table('users')->where('id', $member->id)->update(['verification_status' => 'approved']);
            $this->actingAs($member->fresh())->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'province_id' => 1])->assertSessionHasNoErrors();
            $this->assertSame('pending', $member->fresh()->verification_status);
            $reviewer = User::create(['name' => 'Pemeriksa', 'email' => $role.'@example.test', 'password' => 'password', 'role' => $role, 'province_id' => $role === 'verifikator_pengprov' ? 1 : null]);
            $this->actingAs($reviewer)->post(route('admin.verifications.store', $member), [
                'status' => 'approved', 'checked' => \App\Services\MembershipReview::FIELDS,
                'fingerprint' => app(\App\Services\MembershipReview::class)->fingerprint($member->fresh()),
            ])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame('approved', $member->fresh()->verification_status);
        }
    }
}
