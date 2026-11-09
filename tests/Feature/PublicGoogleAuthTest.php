<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicGoogleAuthTest extends TestCase
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
    }

    public function test_member_registration_saves_city_and_district(): void
    {
        $province = \App\Models\Province::create(['name' => 'SUMATERA UTARA']);
        $city = \App\Models\City::create(['name' => 'KOTA MEDAN', 'province_id' => $province->id]);
        $this->post('/register', [
            'name' => 'Anggota Medan', 'email' => 'medan@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'province_id' => $province->id, 'city_id' => $city->id, 'district' => 'Medan Johor', 'first_coach' => 'Sensei Pertama',
        ])->assertRedirect(route('member.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'medan@example.com', 'city_id' => $city->id, 'district' => 'Medan Johor', 'first_coach' => 'Sensei Pertama']);
    }

    public function test_auth_modal_receives_form_fragments_without_a_page_layout(): void
    {
        foreach (['login', 'register', 'forgot-password'] as $path) {
            $this->withHeader('X-Auth-Modal', '1')->get('/'.$path.'?_auth_modal=1')
                ->assertOk()->assertSee('auth-modal-card')->assertSee('<form', false)
                ->assertDontSee('<!DOCTYPE', false)->assertDontSee('<iframe', false)->assertDontSee('@vite', false);
        }
    }

    public function test_modal_registration_returns_dashboard_url_and_logs_in(): void
    {
        $this->postJson('/register?_auth_modal=1', ['name' => 'Anggota Modal', 'email' => 'modal@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])
            ->assertCreated()->assertJsonPath('redirect', route('member.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_modal_login_returns_intended_destination(): void
    {
        User::create(['name' => 'Anggota Modal', 'email' => 'modal@example.com', 'password' => 'Password123!', 'role' => 'member']);
        $this->withSession(['url.intended' => route('member.certificates')])
            ->postJson('/login?_auth_modal=1', ['login' => 'modal@example.com', 'password' => 'Password123!'])
            ->assertOk()->assertJsonPath('redirect', route('member.certificates'));
        $this->assertAuthenticated();
    }

    public function test_invalid_modal_submission_returns_field_errors_without_redirecting(): void
    {
        $this->postJson('/register?_auth_modal=1', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        $this->postJson('/login?_auth_modal=1', ['login' => 'missing@example.com', 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('login');
        $this->assertGuest();
    }

    public function test_modal_password_reset_returns_feedback_without_redirecting(): void
    {
        \Illuminate\Support\Facades\Password::shouldReceive('sendResetLink')->once()
            ->with(['email' => 'member@example.com'])->andReturn(\Illuminate\Support\Facades\Password::RESET_LINK_SENT);
        $this->postJson('/forgot-password', ['email' => 'member@example.com'])->assertOk()->assertJsonStructure(['message']);
    }

    public function test_registration_rejects_archived_belt_even_when_its_id_is_submitted_directly(): void
    {
        $belt = \App\Models\BeltLevel::create(['name' => 'Kuning Muda', 'order' => 1, 'is_active' => false]);
        $this->post('/register', ['name' => 'Anggota', 'email' => 'old-belt@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'belt_level_id' => $belt->id])
            ->assertSessionHasErrors('belt_level_id');
        $this->assertDatabaseMissing('users', ['email' => 'old-belt@example.com']);
    }

    public function test_district_requires_a_city_and_city_must_belong_to_province(): void
    {
        $province = \App\Models\Province::create(['name' => 'SUMATERA UTARA']);
        $other = \App\Models\Province::create(['name' => 'JAWA BARAT']);
        $city = \App\Models\City::create(['name' => 'BANDUNG', 'province_id' => $other->id]);
        $data = ['name' => 'Anggota', 'email' => 'wilayah@example.com', 'password' => 'Password123!',
            'password_confirmation' => 'Password123!', 'province_id' => $province->id, 'district' => 'Medan Johor'];
        $this->post('/register', $data)->assertSessionHasErrors('city_id');
        $this->post('/register', $data + ['city_id' => $city->id])->assertSessionHasErrors('city_id');
        $this->assertDatabaseMissing('users', ['email' => 'wilayah@example.com']);
    }

    private function flow(): array
    {
        return ['state' => 'valid-state', 'verifier' => 'test-verifier', 'mode' => 'register', 'actor_id' => null, 'expires' => time() + 600];
    }

    private function fakeGoogle(string $email = 'new@example.com', bool $verified = true): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response(['sub' => 'google-123', 'email' => $email, 'name' => 'Anggota Google', 'email_verified' => $verified])]);
    }

    public function test_public_forms_redirect_to_the_landing_modal_but_embedded_forms_render(): void
    {
        $this->get('/login')->assertRedirect('/?auth=login');
        $this->get('/register')->assertRedirect('/?auth=register');
        $this->get('/login?_auth_modal=1')->assertOk()->assertSee('Masuk dengan Google')->assertDontSee('class="auth-brand"', false);
        $this->get('/register?_auth_modal=1')->assertOk()->assertSee('opsional')->assertSee('Daftar dengan Google');
    }

    public function test_manual_member_can_skip_unavailable_choices_and_optional_fields(): void
    {
        $this->post('/register', ['name' => 'Anggota Baru', 'email' => 'member@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])
            ->assertRedirect(route('member.dashboard'));
        $member = User::firstOrFail();
        $this->assertSame('member', $member->role);
        $this->assertNull($member->dojo_id);
        $this->assertNull($member->parent_name);
        $this->assertFalse($member->is_active);
    }

    public function test_google_redirect_uses_state_and_pkce(): void
    {
        $response = $this->get(route('google.redirect', ['mode' => 'register']));
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame(session('google_oauth.state'), $query['state']);
    }

    public function test_bad_oauth_state_does_not_contact_google_or_authenticate(): void
    {
        $this->withSession(['google_oauth' => $this->flow()])->get('/auth/google/callback?state=wrong&code=code')->assertRedirect('/?auth=login');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_google_signup_waits_for_profile_and_uses_verified_email_from_session(): void
    {
        $this->fakeGoogle();
        $this->withSession(['google_oauth' => $this->flow()])->get('/auth/google/callback?state=valid-state&code=code')->assertRedirect('/?auth=register');
        $this->assertGuest();
        $this->assertSame(0, User::count());
        $this->get('/register?_auth_modal=1')->assertOk()->assertSee('Google terverifikasi');
        $this->post('/register', ['name' => 'Profil Lengkap', 'email' => 'spoof@example.com'])->assertRedirect(route('member.dashboard'));
        $member = User::firstOrFail();
        $this->assertSame('new@example.com', $member->email);
        $this->assertSame('google-123', $member->google_id);
        $this->assertNotNull($member->email_verified_at);
        $this->assertFalse($member->is_active);
        $this->assertAuthenticatedAs($member);
    }

    public function test_existing_email_is_not_silently_linked_or_logged_in(): void
    {
        User::create(['name' => 'Existing', 'email' => 'new@example.com', 'password' => 'Password123!']);
        $this->fakeGoogle();
        $this->withSession(['google_oauth' => $this->flow()])->get('/auth/google/callback?state=valid-state&code=code')->assertRedirect('/?auth=login');
        $this->assertGuest();
        $this->assertNull(User::firstOrFail()->google_id);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle('new@example.com', false);
        $this->withSession(['google_oauth' => $this->flow()])->get('/auth/google/callback?state=valid-state&code=code')->assertRedirect('/?auth=register');
        $this->assertGuest();
        $this->assertFalse(session()->has('google_signup'));
    }

    public function test_registration_rejects_a_city_from_a_different_province(): void
    {
        DB::table('provinces')->insert([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
        DB::table('cities')->insert(['id' => 1, 'name' => 'B city', 'province_id' => 2]);
        $this->post('/register', ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'province_id' => 1, 'city_id' => 1])
            ->assertSessionHasErrors('city_id');
        $this->assertSame(0, User::count());
    }
    public function test_linked_google_account_can_log_in_without_creating_another_member(): void
    {
        $user = User::create(['name' => 'Linked', 'email' => 'new@example.com', 'password' => 'Password123!', 'google_id' => 'google-123']);
        $this->fakeGoogle();
        $this->withSession(['google_oauth' => $this->flow()])->get('/auth/google/callback?state=valid-state&code=code')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_expired_google_signup_cannot_bypass_the_password_requirement(): void
    {
        $this->withSession(['google_signup' => ['id' => 'google-123', 'email' => 'new@example.com', 'name' => 'Expired', 'expires' => time() - 1]])
            ->post('/register', ['name' => 'Expired', 'email' => 'new@example.com'])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_mobile_validation_redirect_keeps_the_embedded_registration_form(): void
    {
        $url = route('register', ['_auth_modal' => 1]);
        $this->from($url)->post($url, [])->assertRedirect($url)->assertSessionHasErrors(['name', 'email', 'password']);
        $this->get($url)->assertOk()->assertSee('auth-embedded')->assertSee('Periksa kembali data Anda.')
            ->assertSee('action="' . $url . '"', false);
    }

}
