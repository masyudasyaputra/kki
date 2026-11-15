<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'guide_test', 'database.connections.guide_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('role');
            $t->json('roles')->nullable(); $t->json('positions')->nullable();
            $t->string('registration_type')->default('karateka'); $t->string('verification_status')->default('pending');
            $t->integer('province_id')->nullable(); $t->integer('city_id')->nullable(); $t->integer('belt_level_id')->nullable();
            $t->timestamps();
        });
        Schema::create('belt_levels', function (Blueprint $t) { $t->id(); $t->string('name'); });
    }

    public function test_guide_is_available_to_every_authenticated_role_with_sidebar_entry(): void
    {
        $this->get(route('guide'))->assertRedirect(route('login'));
        foreach (['member', 'pengprov', 'pengcab', 'admin_dojo', 'pb', 'super_admin', 'penguji', 'panitia_ujian', 'verifikator_pengprov'] as $role) {
            $user = User::create(['name' => 'Pengguna '.$role, 'email' => $role.'@example.test', 'role' => $role, 'roles' => [], 'province_id' => 1, 'city_id' => 1]);
            $this->actingAs($user)->get(route('guide'))->assertOk()->assertViewIs('guide')
                ->assertSee('Panduan Sistem')->assertSee('Cari panduan')->assertSee('Langkah penggunaan')
                ->assertSee('MSH-SMT-000123')->assertSee('Intervensi PP')->assertSee('ijazah', false);
        }
    }

    public function test_all_guide_topics_have_searchable_steps_and_notes(): void
    {
        $user = User::create(['name' => 'Karateka', 'email' => 'member@example.test', 'role' => 'member']);
        $response = $this->actingAs($user)->get(route('guide'))->assertOk();
        $sections = $response->viewData('sections'); $index = $response->viewData('guideIndex');
        $this->assertGreaterThanOrEqual(19, count($sections));
        $this->assertCount(count($sections), $index);
        foreach ($sections as $id => $section) {
            $this->assertNotEmpty($section['intro']); $this->assertNotEmpty($section['steps']); $this->assertNotEmpty($section['notes']);
            $entry = collect($index)->firstWhere('id', $id);
            $this->assertStringContainsString(mb_strtolower($section['steps'][0]), $entry['text']);
        }
    }
}
