<?php

namespace Tests\Feature;

use App\Models\{User, Exam, ExamParticipant, ExamScore, CertificateTemplate, ExamCertificate};
use App\Services\{ExamCertificates, CertificatePdfLayout};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, URL};
use Tests\TestCase;

class CertificateSignersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'signer_test', 'database.connections.signer_test' => ['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        Schema::create('users', function(Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('role'); $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->boolean('is_active')->default(true); $t->string('verification_status')->default('approved'); $t->timestamp('email_verified_at')->nullable();
            foreach(['province_id','city_id','dojo_id','belt_level_id'] as $key) $t->integer($key)->nullable(); $t->timestamps();
        });
        Schema::create('belt_levels', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('kyu_dan'); });
        Schema::create('provinces', function(Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('certificate_templates', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('exam_type'); $t->json('settings'); $t->timestamps(); });
        Schema::create('exams', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('exam_type'); $t->string('status'); $t->string('location'); $t->date('execution_date'); $t->json('certificate_settings')->nullable(); $t->timestamps(); });
        Schema::create('exam_participants', function(Blueprint $t) { $t->id(); $t->integer('exam_id'); $t->integer('user_id'); $t->integer('target_belt_id'); $t->integer('dojo_id')->nullable(); $t->timestamps(); });
        Schema::create('exam_scores', function(Blueprint $t) { $t->id(); $t->integer('exam_id'); $t->integer('member_id'); $t->integer('new_belt_level_id'); $t->integer('examiner_id')->nullable(); $t->string('result'); foreach(array_keys(ExamCertificates::MATERIALS) as $key) $t->integer($key)->nullable(); $t->timestamps(); });
        Schema::create('exam_certificates', function(Blueprint $t) { $t->id(); $t->integer('exam_participant_id'); $t->string('number'); $t->json('snapshot'); $t->timestamps(); });
        DB::table('belt_levels')->insert(['id'=>1,'name'=>'Hitam','kyu_dan'=>'DAN IV']);
    }

    private function actor(string $name, string $role = 'pb', bool $dan = true): User
    {
        $user=User::create(['name'=>$name,'email'=>uniqid().'@example.test','role'=>$role,'is_active'=>true,'belt_level_id'=>$dan?1:null]);
        $user->forceFill(['email_verified_at'=>now()])->save(); return $user;
    }

    private function setupSigners(): array
    {
        $admin=$this->actor('Admin','super_admin',false);
        $left=$this->actor('Ketua Kiri'); $center=$this->actor('Ketua Tengah'); $right=$this->actor('Ketua Kanan');
        $template=CertificateTemplate::create(['name'=>'Template DAN','exam_type'=>'dan','settings'=>ExamCertificates::defaults('dan')]);
        $payload=['signer_left_user_id'=>$left->id,'signer_left_title'=>'Ketua Umum','signer_center_user_id'=>$center->id,'signer_center_title'=>'Sekretaris','signer_right_user_id'=>$right->id,'signer_right_title'=>'Ketua Dewan Guru'];
        $this->actingAs($admin); return [$template,$payload,$left,$center,$right];
    }

    public function test_all_three_positions_are_saved_and_distinct_active_dan_users_are_required(): void
    {
        [$template,$payload,$left,$center]=$this->setupSigners();
        $this->get(route('admin.certificate-signers.index'))->assertOk()->assertSee('Penandatangan tengah')->assertSee('signer_center_user_id',false);
        $this->put(route('admin.certificate-signers.update',$template),$payload)->assertSessionHasNoErrors();
        $this->assertSame('Ketua Tengah',$template->fresh()->settings['signer_center_name']);
        $this->put(route('admin.certificate-signers.update',$template),array_replace($payload,['signer_center_user_id'=>$left->id]))->assertSessionHasErrors('signer_left_user_id');
        $center->is_active=false; $center->save();
        $this->put(route('admin.certificate-signers.update',$template),$payload)->assertSessionHasErrors('signer_center_user_id');
    }

    private function certificateData(CertificateTemplate $template): array
    {
        $member=$this->actor('Peserta','member');
        $exam=Exam::create(['name'=>'UKT DAN','exam_type'=>'dan','status'=>'completed','location'=>'Medan','execution_date'=>'2026-11-14']);
        $participant=ExamParticipant::create(['exam_id'=>$exam->id,'user_id'=>$member->id,'target_belt_id'=>1]);
        ExamScore::create(['exam_id'=>$exam->id,'member_id'=>$member->id,'new_belt_level_id'=>1,'result'=>'Lulus']+array_fill_keys(array_keys(ExamCertificates::MATERIALS),80));
        return [$exam,$participant];
    }

    public function test_center_signer_is_in_payload_pdf_values_print_and_verification_snapshot(): void
    {
        [$template,$payload,$left,$center]=$this->setupSigners();
        $this->put(route('admin.certificate-signers.update',$template),$payload)->assertSessionHasNoErrors();
        [$exam,$participant]=$this->certificateData($template);
        $data=app(ExamCertificates::class)->payload($exam,$participant,$template->fresh(),[],true);
        $this->assertSame('Ketua Tengah',$data['template']['signer_center_name']);
        $this->assertSame('DAN IV',CertificatePdfLayout::values($data)['signer_center_rank']);
        $this->assertArrayHasKey('signer_center_title',CertificatePdfLayout::fields());
        $this->view('admin.certificates.print',['data'=>$data,'draft'=>true])->assertSee('signature-row-three')->assertSeeInOrder(['Ketua Kiri','Ketua Tengah','Ketua Kanan']);
        $certificate=ExamCertificate::create(['exam_participant_id'=>$participant->id,'number'=>'KKI-TEST-1','snapshot'=>$data]);
        $center->name='Nama Baru'; $center->save();
        $this->get(URL::signedRoute('certificates.verify',$certificate))->assertOk()->assertSee('Ketua Tengah')->assertDontSee('Nama Baru');
    }

    public function test_existing_two_signer_templates_still_render_without_an_empty_center_position(): void
    {
        [$template,$payload]=$this->setupSigners();
        $settings=$template->settings;
        foreach(['left','right'] as $side) { $user=User::find($payload['signer_'.$side.'_user_id']); foreach(['user_id'=>$user->id,'name'=>$user->name,'rank'=>'DAN IV','title'=>$payload['signer_'.$side.'_title']] as $part=>$value) $settings['signer_'.$side.'_'.$part]=$value; }
        $template->update(['settings'=>$settings]);
        [$exam,$participant]=$this->certificateData($template);
        $data=app(ExamCertificates::class)->payload($exam,$participant,$template,[],true);
        $this->assertSame('',$data['template']['signer_center_name']);
        $this->view('admin.certificates.print',['data'=>$data,'draft'=>true])->assertDontSee('class="signature-row signature-row-three"',false);
    }

    public function test_pdf_templates_with_a_center_signer_require_all_three_center_coordinates(): void
    {
        [$template,$payload]=$this->setupSigners();
        $this->put(route('admin.certificate-signers.update',$template),$payload)->assertSessionHasNoErrors();
        $settings=$template->fresh()->settings;
        $keys=['name','no_id_msh','verification_qr','signer_left_title','signer_left_name','signer_left_rank','signer_right_title','signer_right_name','signer_right_rank'];
        $settings['render_mode']='pdf'; $settings['pdf_path']='certificates/test.pdf'; $settings['pdf_layout']=array_map(fn($key)=>['key'=>$key],$keys);
        $template->update(['settings'=>$settings]);
        [$exam,$participant]=$this->certificateData($template);
        try {
            app(ExamCertificates::class)->payload($exam,$participant,$template,['no_id_msh'=>'MSH-TEST'],true);
            $this->fail('Center coordinates must be configured before issuance.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertStringContainsString('koordinat PDF',implode(' ',$exception->errors()['certificate']));
        }
        foreach(['title','name','rank'] as $part) $settings['pdf_layout'][]=['key'=>'signer_center_'.$part];
        $template->update(['settings'=>$settings]);
        $data=app(ExamCertificates::class)->payload($exam,$participant,$template,['no_id_msh'=>'MSH-TEST'],true);
        $this->assertSame('Ketua Tengah',$data['template']['signer_center_name']);
    }
}
