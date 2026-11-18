<?php

namespace Tests\Feature\Auth;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Event, Http};
use Illuminate\Auth\Events\Registered;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'public_auth_test', 'database.connections.public_auth_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
        Http::preventStrayRequests();
        Event::fake([Registered::class]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_type')->default('karateka');
            $table->string('requested_position')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('google_id')->nullable()->unique();
            $table->string('role')->default('member');
            $table->json('roles')->nullable();
            $table->json('positions')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('district', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('first_coach')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('is_active')->default(false);
            foreach (['province_id', 'city_id', 'dojo_id', 'belt_level_id'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('province_id');
            $table->timestamps();
        });
        Schema::create('dojos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('city_id');
        });
        Schema::create('belt_levels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->id();
            $table->string('name');
            $table->integer('order');
        });
        (require database_path('migrations/2026_11_18_000002_add_privacy_center.php'))->up();
    }



    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register?_auth_modal=1');

        $response->assertStatus(200);
        $response->assertSee('Pengurus Non-Karateka');
    }

    public function test_registration_records_directory_permission(): void
    {
        $this->post('/register', ['name' => 'Consent Member', 'email' => 'consent@example.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'directory_consent' => 1])->assertSessionHasNoErrors();
        $user = \App\Models\User::where('email', 'consent@example.test')->firstOrFail();
        $this->assertTrue($user->public_directory_consent);
        $this->assertDatabaseHas('privacy_events', ['user_id' => $user->id, 'directory_consent' => true]);
    }

    public function test_registration_without_permission_keeps_directory_hidden(): void
    {
        $this->post('/register', ['name' => 'Private Member', 'email' => 'private@example.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertSessionHasNoErrors();
        $this->assertFalse(\App\Models\User::where('email', 'private@example.test')->firstOrFail()->public_directory_consent);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('member.dashboard', absolute: false));
    }

    public function test_official_registration_does_not_grant_privileged_access_or_store_training_data(): void
    {
        $this->post('/register', [
            'registration_type' => 'official', 'requested_position' => 'Sekretaris',
            'name' => 'Pengurus Baru', 'email' => 'pengurus@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'super_admin', 'roles' => ['pb'], 'belt_level_id' => 999,
            'dojo_id' => 999, 'first_coach' => 'Tidak berlaku',
        ])->assertRedirect(route('member.dashboard', absolute: false));

        $user = \App\Models\User::where('email', 'pengurus@example.com')->firstOrFail();
        $this->assertSame('official', $user->registration_type);
        $this->assertSame('Sekretaris', $user->requested_position);
        $this->assertSame('member', $user->role);
        $this->assertFalse($user->hasRole(['pb', 'super_admin']));
        $this->assertFalse($user->is_active);
        $this->assertNull($user->belt_level_id);
        $this->assertNull($user->dojo_id);
        $this->get(route('member.dashboard'))->assertSee('Pengajuan Anda sudah tercatat');
    }

    public function test_official_registration_requires_a_position(): void
    {
        $this->post('/register', [
            'registration_type' => 'official', 'name' => 'Pengurus',
            'email' => 'pengurus@example.com', 'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('requested_position');
        $this->assertGuest();
    }
}
