<?php

namespace Tests\Feature;

use App\Models\{User, Exam, ExamParticipant, ExamScore, CertificateTemplate, ExamCertificate};
use App\Services\ExamCertificates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamCertificateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'certificate_test', 'database.connections.certificate_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); foreach (['name', 'email', 'password', 'role', 'parent_name', 'whatsapp', 'district'] as $field) $t->string($field)->nullable();
            $t->json('roles')->nullable(); $t->boolean('is_active')->default(false); $t->timestamp('email_verified_at')->nullable();
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $field) $t->integer($field)->nullable(); $t->timestamps();
        });
        foreach (['provinces', 'cities', 'dojos', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->boolean('is_active')->default(true); $t->id(); $t->string('name'); $t->integer('province_id')->nullable(); $t->integer('city_id')->nullable();
                $t->string('kyu_dan')->nullable(); $t->integer('order')->default(1); $t->timestamps();
            });
        }
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->date('execution_date'); $t->string('location'); $t->integer('province_id')->nullable(); $t->string('status'); $t->timestamps();
        });
        Schema::create('exam_participants', function (Blueprint $t) {
            $t->id(); foreach (['exam_id', 'user_id', 'dojo_id', 'current_belt_id', 'target_belt_id'] as $field) $t->integer($field)->nullable();
            $t->integer('fee_amount')->default(0); $t->string('payment_status')->default('paid'); $t->string('result')->default('pending'); $t->timestamps();
        });
        Schema::create('exam_scores', function (Blueprint $t) {
            $t->id(); foreach (['exam_id', 'member_id', 'examiner_id', 'new_belt_level_id'] as $field) $t->integer($field)->nullable();
            foreach (['kihon', 'kata', 'kumite', 'result'] as $field) $t->string($field)->nullable(); $t->timestamps();
        });
        Schema::create('exam_examiners', function (Blueprint $t) { $t->id(); $t->integer('exam_id'); $t->integer('user_id'); $t->timestamps(); });
        Schema::create('belt_histories', function (Blueprint $t) { $t->id(); $t->integer('user_id'); $t->integer('exam_id'); $t->integer('belt_level_id'); $t->date('achieved_at'); $t->text('description')->nullable(); $t->timestamps(); });
        (require database_path('migrations/2026_11_01_000003_add_exam_certificates.php'))->up();
        (require database_path('migrations/2026_11_01_000004_add_certificate_delivery.php'))->up();
        (require database_path('migrations/2026_11_09_000001_add_regions_and_exam_review.php'))->up();
        (require database_path('migrations/2026_11_09_000002_restructure_roles_and_exam_assignments.php'))->up();
        DB::table('provinces')->insert([['id' => 1, 'name' => 'SUMATERA UTARA'], ['id' => 2, 'name' => 'JAWA BARAT']]);
        DB::table('cities')->insert(['id' => 1, 'name' => 'MEDAN', 'province_id' => 1]);
        DB::table('belt_levels')->insert([['id' => 1, 'name' => 'Cokelat', 'kyu_dan' => 'Kyu 2'], ['id' => 2, 'name' => 'Hitam', 'kyu_dan' => 'DAN I']]);
    }

    private function actor(string $role = 'pb', int $province = 1): User
    {
        $actor = User::create(['name' => 'Penguji Admin', 'email' => uniqid().'@example.com', 'role' => $role, 'is_active' => true, 'roles' => $role === 'pb' ? ['pengelola_sertifikat_pb', 'panitia_ujian'] : [], 'province_id' => $province, 'city_id' => 1]);
        $actor->forceFill(['email_verified_at' => now()])->save();
        return $actor;
    }

    public function test_approved_black_belt_exam_promotes_rank_and_adds_msh_to_the_same_id(): void
    {
        (require database_path('migrations/2026_11_14_000001_add_member_number_management.php'))->up();
        [$exam, $participant] = $this->fixture('dan', 'open');
        $member = $participant->user;
        $member->update(['belt_level_id' => 1]);
        $before = $member->fresh()->member_number;
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->assertSame($before, $member->fresh()->member_number);
        $reviewer = $this->actor('verifikator_ujian_pb');
        $exam->reviewers()->attach($reviewer);
        $this->actingAs($reviewer)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(2, $member->fresh()->belt_level_id);
        $this->assertSame('MSH-'.$before, $member->fresh()->member_number);
        $this->assertDatabaseHas('member_number_changes', ['member_id' => $member->id, 'before_number' => $before, 'after_number' => 'MSH-'.$before]);
        $this->assertDatabaseHas('belt_histories', ['user_id' => $member->id, 'exam_id' => $exam->id, 'belt_level_id' => 2]);
    }

    public function test_colored_belt_promotes_on_approval_without_waiting_for_certificate(): void
    {
        [$exam, $participant] = $this->fixture('kyu', 'open');
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->assertNull($participant->user->fresh()->belt_level_id);
        $reviewer = $this->actor('verifikator_ujian_pb');
        $exam->reviewers()->attach($reviewer);
        $this->actingAs($reviewer)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(1, $participant->user->fresh()->belt_level_id);
        $this->assertDatabaseHas('belt_histories', ['user_id' => $participant->user_id, 'exam_id' => $exam->id, 'belt_level_id' => 1]);
        $this->assertDatabaseCount('exam_certificates', 0);
    }

    public function test_kyu_is_approved_regionally_and_dan_is_restricted_to_pp(): void
    {
        [$exam, $participant] = $this->fixture('kyu', 'open');
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $outside = $this->actor('pengprov', 2);
        $this->actingAs($outside)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $regional = $this->actor('pengprov');
        $this->actingAs($regional)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $exam->fresh()->review_status);
        $this->assertSame(1, $participant->user->fresh()->belt_level_id);
        [$dan] = $this->fixture('dan', 'open');
        $this->post(route('admin.exams.scoring.finalize', $dan))->assertSessionHasNoErrors();
        $this->actingAs($regional)->post(route('admin.exam-reviews.store', $dan), ['status' => 'approved'])->assertForbidden();
        $this->assertSame('submitted', $dan->fresh()->review_status);
    }

    public function test_pp_can_intervene_in_kyu_and_intervention_is_recorded(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $pp = $this->actor('pb', 2);
        $this->actingAs($pp)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved', 'notes' => 'Pemeriksaan PP'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $exam->fresh()->review_status);
        $this->assertDatabaseHas('exam_reviews', ['exam_id' => $exam->id, 'actor_id' => $pp->id, 'notes' => '[Intervensi PP] Pemeriksaan PP']);
    }

    public function test_regional_certificate_authority_and_template_scope_do_not_extend_to_dan_or_other_regions(): void
    {
        [$kyu, , , $template] = $this->fixture('kyu');
        $template->update(['province_id' => 1, 'city_id' => null]);
        [$dan] = $this->fixture('dan');
        $regional = $this->actor('pengprov'); $outside = $this->actor('pengprov', 2);
        $authority = app(\App\Services\ExamAuthority::class);
        $this->assertTrue($authority->canIssue($regional, $kyu));
        $this->assertFalse($authority->canIssue($regional, $dan));
        $this->assertFalse($authority->canIssue($outside, $kyu));
        $service = app(\App\Services\ExamCertificates::class);
        $this->assertTrue($service->templates($regional)->whereKey($template->id)->exists());
        $this->assertFalse($service->templates($outside)->whereKey($template->id)->exists());
        $this->assertTrue($authority->canIssue($this->actor('pb', 2), $kyu));
    }

    public function test_branch_cannot_approve_a_kyu_session_containing_other_branches(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $branch = $this->actor('pengcab');
        $this->assertTrue(app(\App\Services\ExamAuthority::class)->regional($branch, $exam));
        $other = $this->actor('member'); $other->update(['city_id' => 2]);
        ExamParticipant::create(['exam_id' => $exam->id, 'user_id' => $other->id, 'target_belt_id' => 1]);
        $this->assertFalse(app(\App\Services\ExamAuthority::class)->regional($branch, $exam));
    }

    public function test_regional_template_creation_is_kyu_only_and_scoped_to_the_actor(): void
    {
        $actor = $this->actor('pengprov');
        $payload = ['name' => 'Template Wilayah', 'exam_type' => 'kyu', 'render_mode' => 'builtin', 'heading' => 'Pengprov', 'organization' => 'KKI', 'title' => 'Ijazah', 'color' => '#762137', 'province_id' => 2];
        $this->actingAs($actor)->post(route('admin.certificate-templates.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('certificate_templates', ['name' => 'Template Wilayah', 'exam_type' => 'kyu', 'province_id' => 1, 'city_id' => null]);
        $this->post(route('admin.certificate-templates.store'), array_replace($payload, ['exam_type' => 'dan']))->assertForbidden();
        $template = CertificateTemplate::where('name', 'Template Wilayah')->firstOrFail();
        $this->actingAs($this->actor('pengprov', 2))->post(route('admin.certificate-templates.store'), $payload + ['template_id' => $template->id])->assertNotFound();
    }

    public function test_branch_creates_kyu_in_own_region_and_cannot_create_dan(): void
    {
        (require database_path('migrations/2026_11_15_000007_add_exam_city_scope.php'))->up();
        $actor = $this->actor('pengcab');
        $payload = ['name' => 'UKT Cabang', 'exam_type' => 'kyu', 'execution_date' => '2026-12-01', 'location' => 'Medan', 'status' => 'draft', 'province_id' => 2];
        $this->actingAs($actor)->post(route('admin.exams.store'), $payload)->assertSessionHasNoErrors();
        $exam = Exam::where('name', 'UKT Cabang')->firstOrFail();
        $this->assertEquals(1, $exam->province_id); $this->assertEquals(1, $exam->city_id);
        $this->assertTrue(app(\App\Services\ExamAuthority::class)->regional($actor, $exam));
        $this->post(route('admin.exams.store'), array_replace($payload, ['exam_type' => 'dan']))->assertForbidden();
    }

    public function test_black_belt_target_cannot_be_approved_regionally_under_a_kyu_label(): void
    {
        [$exam] = $this->fixture('dan');
        $exam->update(['exam_type' => 'kyu']);
        $actor = $this->actor('pengprov');
        $authority = app(\App\Services\ExamAuthority::class);
        $this->assertTrue($authority->isDan($exam));
        $this->assertFalse($authority->canReview($actor, $exam));
        $this->assertFalse($authority->canIssue($actor, $exam));
    }

    private function fixture(string $type = 'dan', string $status = 'completed'): array
    {
        $actor = $this->actor(); $this->actingAs($actor);
        $exam = Exam::create(['name' => 'Ujian '.$type, 'exam_type' => $type, 'execution_date' => '2026-10-25', 'location' => 'Medan', 'province_id' => 1, 'status' => $status]);
        $exam->examiners()->attach($actor);
        $member = $this->actor('member');
        $participant = ExamParticipant::create(['exam_id' => $exam->id, 'user_id' => $member->id, 'target_belt_id' => $type === 'dan' ? 2 : 1]);
        $values = array_fill_keys(array_keys(ExamCertificates::materials($type)), 75);
        $score = ExamScore::create($values + ['exam_id' => $exam->id, 'member_id' => $member->id, 'examiner_id' => $actor->id, 'result' => 'Lulus', 'new_belt_level_id' => $type === 'dan' ? 2 : 1]);
        $template = CertificateTemplate::create(['name' => 'Template '.$type, 'exam_type' => $type, 'settings' => array_replace(ExamCertificates::defaults($type), ['signer_left_name' => 'Ketua KKI', 'signer_right_name' => 'Ketua Dewan Guru'])]);
        $exam->update(['certificate_settings' => ['template_id' => $template->id, 'issue_place' => 'Medan', 'issue_date' => '2026-11-01']]);
        return [$exam, $participant, $score, $template];
    }

    public function test_dan_and_kyu_certificates_use_input_scores_and_immutable_snapshots(): void
    {
        foreach (['dan', 'kyu'] as $type) {
            [$exam, $participant, $score, $template] = $this->fixture($type);
            $this->get(route('admin.exams.certificates.index', $exam))->assertRedirect(route('admin.exams.show', $exam));
            $this->get(route('admin.exams.certificates.preview', [$exam, $participant]))->assertOk()->assertSee('DRAF');
            $url = route('admin.exams.certificates.issue', [$exam, $participant]);
            $this->post($url, ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors()->assertRedirect();
            $certificate = ExamCertificate::where('exam_participant_id', $participant->id)->firstOrFail();
            $this->assertSame($type, $certificate->snapshot['type']);
            $this->assertCount($type === 'dan' ? 8 : 6, $certificate->snapshot['scores']);
            $this->assertSame('Tujuh Puluh Lima', $certificate->snapshot['scores']['kihon']['words']);
            $score->update(['kihon' => 80]);
            $this->get(route('admin.exams.certificates.print', [$exam, $participant]))->assertOk()->assertSee('Tujuh Puluh Lima');
            $this->assertSame('75', (string) $certificate->fresh()->snapshot['scores']['kihon']['value']);
            $this->post($url, ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertRedirect();
            $this->assertSame(2, $certificate->fresh()->revision);
            $this->assertCount(1, $certificate->fresh()->previous_versions);
            $this->assertSame('80', (string) $certificate->fresh()->snapshot['scores']['kihon']['value']);
        }
    }

    public function test_certificate_requires_finalized_pass_complete_scores_and_matching_rank(): void
    {
        [$exam, $participant, $score] = $this->fixture('dan', 'open');
        $url = route('admin.exams.certificates.issue', [$exam, $participant]); $identity = ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'];
        $this->post($url, $identity)->assertSessionHasErrors('certificate');
        $exam->update(['status' => 'completed']); $score->update(['result' => 'Tidak Lulus']);
        $this->post($url, $identity)->assertSessionHasErrors('certificate');
        $score->update(['result' => 'Lulus', 'paper' => null]);
        $this->post($url, $identity)->assertSessionHasErrors('certificate');
        $score->update(['paper' => 70, 'new_belt_level_id' => 1]);
        $this->post($url, $identity)->assertSessionHasErrors('certificate');
        $this->assertDatabaseCount('exam_certificates', 0);
    }

    public function test_certificate_access_is_scoped_and_examiners_cannot_issue(): void
    {
        [$exam, $participant] = $this->fixture();
        $this->actingAs($this->actor('pengprov', 2))->get(route('admin.exams.certificates.index', $exam))->assertForbidden();
        $examiner = $this->actor('penguji');
        $this->actingAs($examiner)->get(route('admin.exams.certificates.index', $exam))->assertForbidden();
        $exam->examiners()->attach($examiner);
        $this->get(route('admin.exams.certificates.index', $exam))->assertRedirect(route('admin.exams.show', $exam));
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), [])->assertForbidden();
        $this->actingAs($this->actor('member'))->get(route('admin.exams.certificates.index', $exam))->assertForbidden();
    }

    public function test_scoring_accepts_numbers_preserves_zero_and_uses_authenticated_examiner(): void
    {
        [$exam, $participant, $score] = $this->fixture('kyu', 'open');
        $examiner = $this->actor('penguji'); $exam->examiners()->attach($examiner); $this->actingAs($examiner);
        $this->get(route('admin.exams.scoring', $exam))->assertOk()->assertSee('Ippon Kumite');
        $values = array_fill_keys(array_keys(ExamCertificates::materials('kyu')), 70);
        $values['kihon'] = 0; $values['examiner_id'] = 999; $values['result'] = 'Lulus'; $values['new_belt_level_id'] = 1;
        $url = route('admin.exams.scoring.store', $exam);
        $this->postJson($url, ['scores' => [$participant->user_id => $values]])->assertOk();
        $this->assertSame($examiner->id, $score->fresh()->examiner_id);
        $this->assertSame('0', $score->fresh()->kihon);
        $values['kata'] = 101;
        $this->postJson($url, ['scores' => [$participant->user_id => $values]])->assertUnprocessable();
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertForbidden();
    }

    public function test_template_type_validation_and_legacy_scores_are_not_converted_to_guessed_numbers(): void
    {
        [$exam, $participant, $score, $template] = $this->fixture('kyu');
        $score->update(['kihon' => 'Baik']);
        $data = app(ExamCertificates::class)->payload($exam, $participant, $template, [], true);
        $this->assertSame('Baik', $data['scores']['kihon']['value']);
        $this->assertSame('Baik', $data['scores']['kihon']['words']);
        $this->assertSame('Nol', ExamCertificates::words(0));
        $this->assertSame('Seratus', ExamCertificates::words(100));
        $this->assertSame('Tujuh Puluh Lima Koma Lima', ExamCertificates::words('75.50'));
        $this->post(route('admin.exams.certificates.settings', $exam), ['exam_type' => 'dan', 'template_id' => $template->id, 'issue_place' => 'Medan', 'issue_date' => '2026-11-01'])->assertNotFound();
    }

    public function test_template_management_can_create_edit_and_select_a_template(): void
    {
        [$exam] = $this->fixture('dan');
        $settings = array_replace(ExamCertificates::defaults('dan'), ['signer_left_name' => 'Ketua Nasional', 'signer_right_name' => 'Ketua Dewan Guru']);
        $payload = $settings + ['name' => 'Template DAN Baru', 'exam_type' => 'dan'];
        $this->post(route('admin.certificate-templates.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $template = CertificateTemplate::where('name', 'Template DAN Baru')->firstOrFail();
        $this->post(route('admin.certificate-templates.store'), array_replace($payload, ['template_id' => $template->id, 'signer_right_name' => 'Ketua Baru']))->assertSessionHasNoErrors();
        $this->assertSame('Ketua Baru', $template->fresh()->settings['signer_right_name']);
        $this->post(route('admin.exams.certificates.settings', $exam), ['exam_type' => 'dan', 'template_id' => $template->id, 'issue_place' => 'Jakarta', 'issue_date' => '2026-11-01'])->assertSessionHasNoErrors();
        $this->assertSame($template->id, (int) $exam->fresh()->certificate_settings['template_id']);
        $this->actingAs($this->actor('pengprov'));
        $this->post(route('admin.certificate-templates.store'), $payload + ['template_id' => $template->id])->assertForbidden();
    }

    public function test_admin_finalization_updates_belt_on_approval_and_delivery_preserves_it(): void
    {
        [$exam, $participant] = $this->fixture('kyu', 'open');
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertRedirect(route('admin.exam-reviews.show', $exam));
        $this->assertSame('submitted', $exam->fresh()->review_status);
        $this->assertSame('open', $exam->fresh()->status);
        $reviewer = $this->actor('verifikator_ujian_pb');
        $exam->reviewers()->attach($reviewer);
        $this->actingAs($reviewer);
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->actingAs($this->actor());
        $this->assertSame('completed', $exam->fresh()->status);
        $this->assertSame(1, $participant->user->fresh()->belt_level_id);
        $this->assertDatabaseCount('belt_histories', 1);
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('exam_certificates', 1);
        $this->post(route('admin.exams.certificates.send', [$exam, $participant]))->assertSessionHasNoErrors();
        $this->assertSame(1, $participant->user->fresh()->belt_level_id);
        $this->assertDatabaseHas('belt_histories', ['user_id' => $participant->user_id, 'exam_id' => $exam->id]);
    }

    public function test_committee_submission_is_locked_and_can_be_revised_and_resubmitted(): void
    {
        [$exam, $participant, $score] = $this->fixture('kyu', 'open');
        $committee = $this->actor('panitia_ujian');
        $exam->examiners()->attach($committee);
        $this->actingAs($committee);
        $this->get(route('admin.exams.show', $exam))->assertOk();
        $this->get(route('admin.exams.scoring', $exam))->assertOk()->assertSee('Kirim Hasil ke Pusat');
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->get(route('admin.exam-reviews.show', $exam))->assertOk()->assertSee($participant->user->name);
        $this->postJson(route('admin.exams.scoring.store', $exam), ['scores' => [$participant->user_id => ['kihon' => 90]]])->assertUnprocessable();
        $this->delete(route('admin.exams.remove-member', $participant))->assertUnprocessable();
        $this->delete(route('admin.exams.bulk-remove-member', $exam), ['participant_ids' => [$participant->id]])->assertUnprocessable();
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $reviewer = $this->actor('verifikator_ujian_pb');
        $exam->reviewers()->attach($reviewer);
        $this->actingAs($reviewer);
        $this->get(route('admin.exam-reviews.index'))->assertOk()->assertSee($exam->name);
        $this->get(route('admin.exam-reviews.show', $exam))->assertOk()->assertSee('75');
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'revision'])->assertSessionHasErrors('notes');
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'revision', 'notes' => 'Periksa nilai kihon'])->assertSessionHasNoErrors();
        $this->actingAs($committee);
        $values = array_fill_keys(array_keys(ExamCertificates::materials('kyu')), 85);
        $this->postJson(route('admin.exams.scoring.store', $exam), ['scores' => [$participant->user_id => $values + ['result' => 'Lulus']]])->assertOk();
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->actingAs($reviewer);
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'revision', 'notes' => 'Ulang'])->assertUnprocessable();
        $this->assertSame('approved', $exam->fresh()->review_status);
        $this->assertSame('completed', $exam->fresh()->status);
        $this->assertDatabaseCount('exam_reviews', 4);
        $this->assertSame('75', (string)\App\Models\ExamReview::oldest('id')->first()->snapshot['participants'][0]['score']['kihon']);
        $this->assertSame('85', (string)$score->fresh()->kihon);
    }

    public function test_submission_requires_complete_scores_and_assigned_committee(): void
    {
        [$exam, $participant, $score] = $this->fixture('dan', 'open');
        $committee = auth()->user();
        $this->actingAs($this->actor('panitia_ujian'));
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertForbidden();
        $this->get(route('admin.exams.show', $exam))->assertForbidden();
        $this->actingAs($committee);
        $score->update(['paper' => null]);
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasErrors('submission');
        $this->assertSame('draft', $exam->fresh()->review_status);
        $this->assertDatabaseCount('exam_reviews', 0);
    }

    public function test_submitter_cannot_verify_their_own_exam(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $actor = auth()->user();
        $actor->update(['roles' => ['verifikator_ujian_pb', 'panitia_ujian']]);
        $exam->reviewers()->attach($actor);
        $this->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
    }

    public function test_regions_are_scoped_and_profile_saves_dojo_and_first_coach(): void
    {
        $actor = $this->actor('pengprov');
        $this->actingAs($actor);
        $this->get(route('admin.members.create'))->assertOk()->assertSee('Pelatih awal')->assertSee('Nama dojo');
        $this->get(route('admin.regions.index'))->assertOk();
        $this->post(route('admin.regions.store'), ['type' => 'city', 'province_id' => 2, 'name' => 'Kota Lain'])->assertForbidden();
        $this->post(route('admin.regions.store'), ['type' => 'district', 'city_id' => 1, 'name' => 'Medan Johor'])->assertSessionHasNoErrors();
        $this->post(route('admin.regions.store'), ['type' => 'district', 'city_id' => 1, 'name' => 'Medan Johor'])->assertSessionHasErrors('name');
        $this->get('/api/districts/1')->assertOk()->assertJsonFragment(['name' => 'Medan Johor']);
        $dojo = \App\Models\Dojo::create(['name' => 'Dojo Medan', 'city_id' => 1]);
        $member = $this->actor('member');
        $this->actingAs($member);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Pelatih awal')->assertSee('Dojo Medan');
        $this->patch(route('profile.update'), ['name' => $member->name, 'email' => $member->email, 'dojo_id' => $dojo->id, 'first_coach' => 'Sensei Awal'])->assertSessionHasNoErrors();
        $this->assertSame('Sensei Awal', $member->fresh()->first_coach);
        $this->assertSame($dojo->id, $member->fresh()->dojo_id);
    }

    public function test_certificate_actions_require_explicit_pb_permission_for_every_region(): void
    {
        [$exam, $participant, , $template] = $this->fixture('kyu');
        foreach (['pb', 'super_admin', 'dewan_guru_pusat', 'pengprov', 'pengcab', 'verifikator_anggota_pb', 'verifikator_ujian_pb'] as $role) {
            $user = $this->actor($role);
            $user->update(['roles' => []]);
            $this->actingAs($user);
            $this->get(route('admin.certificate-templates.index'))->assertForbidden();
            $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertForbidden();
            $this->post(route('admin.exams.certificates.send-all', $exam))->assertForbidden();
            $this->post(route('admin.exams.certificates.settings', $exam), [])->assertForbidden();
            $this->post(route('admin.exams.certificates.identity', [$exam, $participant]), [])->assertForbidden();
            $this->delete(route('admin.certificate-templates.destroy', $template))->assertForbidden();
        }
        $manager = $this->actor('member', 2);
        $manager->update(['roles' => ['pengelola_sertifikat_pb']]);
        $this->actingAs($manager);
        $this->get('/dashboard')->assertRedirect(route('admin.exam-reviews.index'));
        $this->get(route('admin.certificate-templates.index'))->assertOk()->assertSee('Template Sertifikat');
        $this->get(route('admin.exams.show', $exam))->assertOk()->assertSee('Kirim semua sertifikat')->assertDontSee('Daftarkan Anggota Baru ke Sesi Ujian');
        $this->get(route('admin.exams.scoring', $exam))->assertOk()->assertDontSee('Kirim Hasil ke Pusat');
        $this->post(route('admin.exams.register-member', $exam), ['user_ids' => [$participant->user_id]])->assertForbidden();
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $this->post(route('admin.exams.certificates.send', [$exam, $participant]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('exam_certificates', 1);
        $this->assertSame('PENGURUS PUSAT', \App\Models\ExamCertificate::first()->snapshot['template']['heading']);
    }

    public function test_delivering_legacy_certificate_keeps_snapshot_and_uses_active_member_rank(): void
    {
        [$exam, $participant] = $this->fixture('kyu');
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $certificate = ExamCertificate::firstOrFail();
        $snapshot = $certificate->snapshot;
        $oldBelt = $participant->targetBelt;
        $oldBelt->update(['is_active' => false]);
        $newBelt = \App\Models\BeltLevel::create(['name' => 'Coklat Strip', 'kyu_dan' => 'Kyu 2', 'order' => 6, 'is_active' => true]);
        $this->post(route('admin.exams.certificates.send', [$exam, $participant]))->assertSessionHasNoErrors();
        $this->assertSame($newBelt->id, $participant->user->fresh()->belt_level_id);
        $this->assertSame($snapshot, $certificate->fresh()->snapshot);
        $this->assertDatabaseHas('belt_histories', ['user_id' => $participant->user_id, 'belt_level_id' => $oldBelt->id]);
    }

    public function test_exam_verifier_can_be_dewan_guru_or_other_user_but_requires_assignment(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $operator = auth()->user();
        $guru = $this->actor('dewan_guru_pusat');
        $guru->update(['positions' => ['dewan_guru_pusat'], 'roles' => ['verifikator_ujian_pb']]);
        $other = $this->actor('member', 2);
        $other->update(['positions' => ['member'], 'roles' => ['verifikator_ujian_pb']]);
        $this->actingAs($operator)->post(route('admin.exams.scoring.finalize', $exam))->assertSessionHasNoErrors();
        $this->actingAs($other)->get(route('admin.exam-reviews.show', $exam))->assertForbidden();
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($guru)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $this->get(route('admin.oversight'))->assertOk()->assertSee($exam->name);
        $this->actingAs($operator)->put(route('admin.exam-reviews.assign', $exam), ['reviewer_ids' => [$guru->id, $other->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('access_audits', ['event' => 'exam.reviewers.assigned', 'exam_id' => $exam->id]);
        $this->actingAs($other)->get(route('admin.exam-reviews.show', $exam))->assertOk()->assertSee('Simpan verifikasi');
        $other->update(['roles' => []]);
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($guru)->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $exam->fresh()->review_status);
    }

    public function test_dewan_guru_position_does_not_grant_verification_or_certificate_rights(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $guru = $this->actor('dewan_guru_pusat');
        $this->actingAs($guru);
        $this->get('/dashboard')->assertRedirect(route('admin.oversight'));
        $this->get(route('admin.exams.show', $exam))->assertOk()->assertDontSee('Daftarkan Anggota Baru ke Sesi Ujian');
        $this->get(route('admin.verifications.index'))->assertForbidden();
        $this->post(route('admin.exam-reviews.store', $exam), ['status' => 'approved'])->assertForbidden();
        $this->postJson(route('admin.exams.scoring.store', $exam), ['scores' => []])->assertForbidden();
        $this->actingAs($this->actor('pengprov'))->put(route('admin.exam-reviews.assign', $exam), ['reviewer_ids' => [$guru->id]])->assertForbidden();
    }

    public function test_committee_cannot_be_assigned_to_verify_same_session(): void
    {
        [$exam] = $this->fixture('kyu', 'open');
        $committee = $this->actor('panitia_ujian');
        $committee->update(['roles' => ['verifikator_ujian_pb']]);
        $exam->examiners()->attach($committee);
        $this->put(route('admin.exam-reviews.assign', $exam), ['reviewer_ids' => [$committee->id]])->assertSessionHasErrors('reviewer_ids.0');
        $reviewer = $this->actor('member'); $reviewer->update(['roles' => ['verifikator_ujian_pb', 'penguji']]);
        $this->put(route('admin.exam-reviews.assign', $exam), ['reviewer_ids' => [$reviewer->id]])->assertSessionHasNoErrors();
        $this->put(route('admin.exams.examiners.update', $exam), ['examiner_ids' => [$reviewer->id]])->assertUnprocessable();
    }

    public function test_user_positions_and_permissions_are_stored_separately_and_audited(): void
    {
        $this->actingAs($this->actor('super_admin'));
        $payload = ['name' => 'Dewan Guru Nasional', 'email' => 'guru-baru@example.com', 'password' => 'Password123!',
            'positions' => ['dewan_guru_pusat'], 'roles' => ['verifikator_ujian_pb']];
        $this->post(route('admin.users.store'), $payload)->assertSessionHasNoErrors();
        $guru = User::where('email', 'guru-baru@example.com')->firstOrFail();
        $this->assertSame(['dewan_guru_pusat'], $guru->positions);
        $this->assertSame(['verifikator_ujian_pb'], $guru->roles);
        $this->assertSame('dewan_guru_pusat', $guru->role);
        $this->get(route('admin.users.edit', $guru))->assertOk()->assertSee('Jabatan & pengelola sistem', false)->assertSee('Hak akses tambahan');
        $this->get(route('admin.access-audits.index'))->assertOk()->assertSee('user.created');
        $this->actingAs($this->actor('pengprov'));
        $payload['email'] = 'forged@example.com';
        $payload['province_id'] = 1;
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('roles.0');
        $this->put(route('admin.users.update', $guru), $payload + ['is_active' => 1])->assertForbidden();
    }

    public function test_pdf_layout_is_validated_saved_and_frozen_in_issued_certificate(): void
    {
        [$exam, $participant, $score, $template] = $this->fixture('dan');
        $settings = $template->settings + ['render_mode' => 'pdf', 'pdf_path' => 'certificates/pdf-templates/example.pdf'];
        $template->update(['settings' => $settings]);
        $this->get(route('admin.certificate-templates.layout', $template))->assertOk()->assertSee('Editor Koordinat');
        $payload = ['version' => hash('sha256', json_encode($template->fresh()->settings)), 'pages' => [['width' => 297, 'height' => 210]],
            'layout' => [['key' => 'name', 'page' => 1, 'x' => 40, 'y' => 70, 'width' => 150, 'font_size' => 20, 'align' => 'center', 'font' => 'serif', 'bold' => true, 'color' => '#000000']]];
        $url = route('admin.certificate-templates.layout.save', $template);
        $bad = $payload; $bad['layout'][0]['x'] = 280;
        $this->postJson($url, $bad)->assertUnprocessable();
        $bad = $payload; $bad['layout'][0]['key'] = 'password';
        $this->postJson($url, $bad)->assertUnprocessable();
        $saved = $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertConflict();
        $this->assertSame(40, $template->fresh()->settings['pdf_layout'][0]['x']);
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors()->assertRedirect();
        $certificate = ExamCertificate::firstOrFail();
        $this->assertSame(40, $certificate->snapshot['template']['pdf_layout'][0]['x']);
        $this->get(route('admin.exams.certificates.print', [$exam, $participant]))->assertOk()->assertSee('certificate-output-config');
        $settings = $template->fresh()->settings; $settings['pdf_layout'][0]['x'] = 60; $template->update(['settings' => $settings]);
        $this->assertSame(40, $certificate->fresh()->snapshot['template']['pdf_layout'][0]['x']);
        $this->actingAs($this->actor('pengprov'));
        $this->get(route('admin.certificate-templates.layout', $template))->assertForbidden();
    }

    public function test_pdf_mode_requires_file_and_coordinates_before_issuance(): void
    {
        [$exam, $participant, $score, $template] = $this->fixture();
        $payload = $template->settings + ['render_mode' => 'pdf', 'name' => 'PDF Tanpa File', 'exam_type' => 'dan'];
        $this->post(route('admin.certificate-templates.store'), $payload)->assertSessionHasErrors('template_pdf');
        $settings = $template->settings + ['render_mode' => 'pdf', 'pdf_path' => 'example.pdf'];
        $template->update(['settings' => $settings]);
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasErrors('certificate');
    }

    public function test_pdf_upload_opens_editor_and_replacement_clears_old_coordinates(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$exam, , , $template] = $this->fixture();
        $pdf = "%PDF-1.4\n";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents 4 0 R >>', "<< /Length 0 >>\nstream\n\nendstream"];
        $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        $payload = ['name' => 'Desain PDF', 'exam_type' => 'dan', 'render_mode' => 'pdf', 'color' => '#000000',
            'template_pdf' => \Illuminate\Http\UploadedFile::fake()->createWithContent('desain.pdf', $pdf)];
        $this->post(route('admin.certificate-templates.store'), $payload)->assertSessionHasNoErrors();
        $created = CertificateTemplate::where('name', 'Desain PDF')->firstOrFail();
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($created->settings['pdf_path']);
        $this->get(route('admin.certificate-templates.layout', $created))->assertOk();
        $previous = $created->settings['pdf_path'];
        $settings = $created->settings; $settings['pdf_layout'] = [['key' => 'name']]; $created->update(['settings' => $settings]);
        $payload['template_id'] = $created->id;
        $payload['template_pdf'] = \Illuminate\Http\UploadedFile::fake()->createWithContent('baru.pdf', $pdf);
        $this->post(route('admin.certificate-templates.store'), $payload)->assertRedirect(route('admin.certificate-templates.layout', $created));
        $this->assertSame([], $created->fresh()->settings['pdf_layout']);
        $this->assertNotSame($previous, $created->fresh()->settings['pdf_path']);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($previous);
    }

    public function test_template_delete_blocks_active_use_and_preserves_issued_certificates(): void
    {
        [$exam, $participant, , $template] = $this->fixture();
        $url = route('admin.certificate-templates.destroy', $template);
        $this->delete($url)->assertSessionHasErrors('template');
        $settings = $exam->certificate_settings; $settings['template_id'] = (string) $template->id; $exam->update(['certificate_settings' => $settings]);
        $this->delete($url)->assertSessionHasErrors('template');
        $this->assertDatabaseHas('certificate_templates', ['id' => $template->id]);
        $this->post(route('admin.exams.certificates.issue', [$exam, $participant]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $exam->update(['certificate_settings' => []]);
        $this->delete($url)->assertRedirect(route('admin.certificate-templates.index'));
        $this->assertDatabaseMissing('certificate_templates', ['id' => $template->id]);
        $this->assertDatabaseCount('exam_certificates', 1);
        $this->get(route('admin.exams.certificates.print', [$exam, $participant]))->assertOk()->assertSee('Tujuh Puluh Lima');
    }

    public function test_provincial_admin_cannot_manage_any_certificate_template(): void
    {
        [$exam, , , $template] = $this->fixture();
        $this->actingAs($this->actor('pengprov'));
        $this->delete(route('admin.certificate-templates.destroy', $template))->assertForbidden();
        $own = CertificateTemplate::create(['name' => 'Template Provinsi', 'exam_type' => 'dan', 'province_id' => 1, 'settings' => []]);
        $other = CertificateTemplate::create(['name' => 'Template Provinsi Lain', 'exam_type' => 'dan', 'province_id' => 2, 'settings' => []]);
        $this->delete(route('admin.certificate-templates.destroy', $other))->assertForbidden();
        $this->delete(route('admin.certificate-templates.destroy', $own))->assertForbidden();
        $this->assertDatabaseHas('certificate_templates', ['id' => $own->id]);
    }

    public function test_master_templates_are_managed_without_exam_and_selected_independently_per_session(): void
    {
        $this->actingAs($this->actor());
        $this->get(route('admin.certificate-templates.index'))->assertOk()->assertSee('Pengaturan master');
        $payload = array_replace(ExamCertificates::defaults('dan'), ['name' => 'Template Master DAN', 'exam_type' => 'dan', 'signer_left_name' => 'Ketua', 'signer_right_name' => 'Dewan Guru']);
        $this->post(route('admin.certificate-templates.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.certificate-templates.index'));
        $template = CertificateTemplate::where('name', 'Template Master DAN')->firstOrFail();
        $this->get(route('admin.certificate-templates.index'))->assertOk()->assertSee('Edit template Template Master DAN')->assertSee('Hapus template Template Master DAN');
        $sessions = [];
        foreach (['Ujian A', 'Ujian B'] as $name) {
            $exam = Exam::create(['name' => $name, 'exam_type' => 'dan', 'location' => 'Medan', 'execution_date' => '2026-11-01', 'status' => 'open']);
            $this->post(route('admin.exams.certificates.settings', $exam), ['exam_type' => 'dan', 'template_id' => $template->id, 'issue_place' => 'Medan', 'issue_date' => '2026-11-01'])->assertSessionHasNoErrors();
            $sessions[] = $exam;
        }
        $this->assertSame($sessions[0]->fresh()->certificate_settings['template_id'], $sessions[1]->fresh()->certificate_settings['template_id']);
        $this->get(route('admin.exams.show', $sessions[0]))->assertOk()->assertSee('Template Master DAN')->assertDontSee('Manajemen Sertifikat')->assertDontSee('template-new');
        $this->actingAs($this->actor('penguji'))->get(route('admin.certificate-templates.index'))->assertForbidden();
    }

    public function test_delivery_updates_member_once_and_only_recipient_can_open_certificate(): void
    {
        [$exam, $person] = $this->fixture('dan');
        $identityUrl = route('admin.exams.certificates.identity', [$exam, $person]);
        $this->post($identityUrl, ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $sendUrl = route('admin.exams.certificates.send', [$exam, $person]);
        $this->post($sendUrl)->assertSessionHasNoErrors()->assertRedirect(route('admin.exams.show', $exam));
        $certificate = ExamCertificate::where('exam_participant_id', $person->id)->firstOrFail();
        $this->assertNotNull($certificate->sent_at);
        $this->assertSame(2, $person->user->fresh()->belt_level_id);
        $this->assertSame('passed', $person->fresh()->result);
        $this->assertDatabaseCount('belt_histories', 1);
        $this->post($sendUrl)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('exam_certificates', 1);
        $this->assertDatabaseCount('belt_histories', 1);
        $this->assertSame($certificate->sent_at->toIso8601String(), $certificate->fresh()->sent_at->toIso8601String());
        $this->actingAs($person->user)->get(route('member.certificates.show', $certificate))->assertOk()->assertSee($certificate->number);
        $this->actingAs($this->actor('member'))->get(route('member.certificates.show', $certificate))->assertNotFound();
    }

    public function test_bulk_delivery_skips_ineligible_members_and_sends_ready_certificates(): void
    {
        [$exam, $ready] = $this->fixture();
        $ready->certificate_identity = ['birth_place' => 'Medan', 'birth_date' => '2000-01-01']; $ready->save();
        $failedMember = $this->actor('member');
        $failed = ExamParticipant::create(['exam_id' => $exam->id, 'user_id' => $failedMember->id, 'target_belt_id' => 2]);
        ExamScore::create(['exam_id' => $exam->id, 'member_id' => $failedMember->id, 'examiner_id' => auth()->id(), 'result' => 'Tidak Lulus', 'new_belt_level_id' => 2]);
        $missingMember = $this->actor('member');
        $missing = ExamParticipant::create(['exam_id' => $exam->id, 'user_id' => $missingMember->id, 'target_belt_id' => 2]);
        ExamScore::create(array_fill_keys(array_keys(ExamCertificates::materials('dan')), 75) + ['exam_id' => $exam->id, 'member_id' => $missingMember->id, 'examiner_id' => auth()->id(), 'result' => 'Lulus', 'new_belt_level_id' => 2]);
        $this->post(route('admin.exams.certificates.send-all', $exam))->assertSessionHas('certificate_delivery_errors', fn ($items) => count($items) === 2);
        $this->assertDatabaseCount('exam_certificates', 1);
        $this->assertDatabaseCount('belt_histories', 1);
        $this->assertSame(2, $ready->user->fresh()->belt_level_id);
        $this->assertNull($failedMember->fresh()->belt_level_id);
        $this->assertNull($missingMember->fresh()->belt_level_id);
        $this->get(route('admin.exams.show', $exam))->assertOk()->assertSee('Kirim semua sertifikat')->assertSee('Kirim sertifikat')->assertDontSee('Manajemen Sertifikat');
    }

    public function test_unsent_certificate_is_private_and_delivery_uses_issued_rank_snapshot(): void
    {
        [$exam, $person, $score] = $this->fixture('dan');
        $admin = auth()->user();
        $this->post(route('admin.exams.certificates.issue', [$exam, $person]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01'])->assertSessionHasNoErrors();
        $certificate = ExamCertificate::firstOrFail();
        $this->actingAs($person->user)->get(route('member.certificates.show', $certificate))->assertNotFound();
        $score->update(['new_belt_level_id' => 1]);
        $this->actingAs($admin)->post(route('admin.exams.certificates.send', [$exam, $person]))->assertSessionHasNoErrors();
        $this->assertSame(2, $person->user->fresh()->belt_level_id);
    }

    public function test_delivery_checks_scope_and_does_not_replace_newer_promotion(): void
    {
        [$exam, $person] = $this->fixture('kyu');
        $admin = auth()->user();
        $person->certificate_identity = ['birth_place' => 'Medan', 'birth_date' => '2000-01-01']; $person->save();
        $this->actingAs($this->actor('pengprov', 2))->post(route('admin.exams.certificates.send', [$exam, $person]))->assertForbidden();
        $this->assertDatabaseCount('exam_certificates', 0);
        $newer = Exam::create(['name' => 'Ujian Baru', 'exam_type' => 'dan', 'execution_date' => '2026-11-01', 'status' => 'completed', 'location' => 'Medan']);
        $person->user->update(['belt_level_id' => 2]);
        \App\Models\BeltHistory::create(['user_id' => $person->user_id, 'exam_id' => $newer->id, 'belt_level_id' => 2, 'achieved_at' => '2026-11-01']);
        $this->actingAs($admin)->post(route('admin.exams.certificates.send', [$exam, $person]))->assertSessionHasNoErrors();
        $this->assertSame(2, $person->user->fresh()->belt_level_id);
        $this->assertDatabaseCount('belt_histories', 2);
    }
    public function test_custom_certificate_number_is_used_in_issue_delivery_and_revision(): void
    {
        [$exam, $person, $score, $template] = $this->fixture();
        $signer = $this->actor('member'); $signer->update(['belt_level_id' => 2]);
        $template->update(['settings' => array_replace($template->settings, ['signer_left_user_id' => $signer->id, 'signer_right_user_id' => $signer->id])]);
        $identity = ['certificate_number' => 'KKI/CUSTOM/0001/2030', 'birth_place' => 'Medan', 'birth_date' => '2000-01-01'];
        $this->post(route('admin.exams.certificates.identity', [$exam, $person]), $identity)->assertSessionHasNoErrors();
        $this->post(route('admin.exams.certificates.send', [$exam, $person]))->assertSessionHasNoErrors();
        $certificate = ExamCertificate::where('exam_participant_id', $person->id)->firstOrFail();
        $this->assertSame($identity['certificate_number'], $certificate->number);
        $this->assertSame($certificate->number, $certificate->snapshot['number']);
        $this->assertNotNull($certificate->sent_at);
        $identity['certificate_number'] = 'KKI/CUSTOM/0002/2030';
        $this->post(route('admin.exams.certificates.identity', [$exam, $person]), $identity)->assertSessionHasNoErrors();
        $certificate->refresh();
        $this->assertSame('KKI/CUSTOM/0002/2030', $certificate->number);
        $this->assertSame($certificate->number, $certificate->snapshot['number']);
        $this->assertNull($certificate->sent_at);
        $this->assertSame('KKI/CUSTOM/0001/2030', $certificate->previous_versions[0]['snapshot']['number']);
        $this->post(route('admin.exams.certificates.issue', [$exam, $person]), $identity)->assertSessionHasNoErrors();
        $this->assertSame('KKI/CUSTOM/0002/2030', $certificate->fresh()->number);
    }

    public function test_custom_certificate_number_rejects_duplicates_and_out_of_scope_actors(): void
    {
        [$exam, $person] = $this->fixture();
        ExamCertificate::create(['exam_participant_id' => 999, 'issued_by' => auth()->id(), 'number' => 'DUPLICATE/001', 'snapshot' => []]);
        $url = route('admin.exams.certificates.identity', [$exam, $person]);
        $this->post($url, ['certificate_number' => 'DUPLICATE/001'])->assertSessionHasErrors('certificate_number');
        $this->assertNull($person->fresh()->certificate_identity);
        $this->post($url, ['certificate_number' => '<invalid>'])->assertSessionHasErrors('certificate_number');
        $this->actingAs($this->actor('pengprov', 2))->post($url, ['certificate_number' => 'OTHER/001'])->assertForbidden();
    }

    public function test_correspondence_service_can_issue_certificates_but_cannot_edit_scores(): void
    {
        [$exam, $person, $score, $template] = $this->fixture();
        $signer = $this->actor('member'); $signer->update(['belt_level_id' => 2]);
        $template->update(['settings' => array_replace($template->settings, ['signer_left_user_id' => $signer->id, 'signer_right_user_id' => $signer->id])]);
        $actor = $this->actor('bantuan_admin_surat_menyurat');
        $this->actingAs($actor)->post(route('admin.exams.certificates.issue', [$exam, $person]), ['birth_place' => 'Medan', 'birth_date' => '2000-01-01', 'certificate_number' => 'KKI/ADMIN/SURAT/001'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exam_certificates', ['exam_participant_id' => $person->id, 'number' => 'KKI/ADMIN/SURAT/001', 'issued_by' => $actor->id]);
        $exam->update(['status' => 'open', 'review_status' => 'draft']);
        $this->post(route('admin.exams.scoring.store', $exam), [])->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

}
