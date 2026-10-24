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
            $table->string('parent_name')->nullable();
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
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('province_id');
        });
        Schema::create('dojos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('city_id');
        });
        Schema::create('belt_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order');
        });
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
            ->assertRedirect(route('dashboard'));
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
        $this->post('/register', ['name' => 'Profil Lengkap', 'email' => 'spoof@example.com'])->assertRedirect(route('dashboard'));
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
