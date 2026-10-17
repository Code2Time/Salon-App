<?php

namespace Tests\Feature\Auth;

use App\Models\ErrorLog;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private array $statefulHeaders = [
        'Origin' => 'http://localhost:8000',
        'Referer' => 'http://localhost:8000/login',
        'Accept' => 'application/json',
    ];

    public function test_new_user_can_register_as_customer_with_fixed_development_otp(): void
    {
        $this->withHeaders($this->statefulHeaders)
            ->postJson('/api/auth/otp/request', ['mobile' => '0912 345 6789'])
            ->assertOk()
            ->assertJson(['message' => 'کد ورود ارسال شد.']);

        $response = $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', [
            'mobile' => '+989123456789',
            'code' => '1111',
            'mode' => 'register',
            'first_name' => 'مشتری',
            'last_name' => 'جدید',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.mobile', '09123456789')
            ->assertJsonPath('user.first_name', 'مشتری')
            ->assertJsonPath('user.last_name', 'جدید')
            ->assertJsonPath('user.profile_completed', true)
            ->assertJsonPath('user.system_role', null)
            ->assertJsonPath('user.abilities.access_panel', false)
            ->assertJsonPath('user.is_first_login', true)
            ->assertJsonPath('user.home_path', '/app');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['mobile' => '09123456789', 'system_role' => null]);
    }

    public function test_registration_cannot_assign_a_privileged_role(): void
    {
        $this->requestCode('09123456789');

        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', [
            'mobile' => '09123456789',
            'code' => '1111',
            'mode' => 'register',
            'first_name' => 'کاربر',
            'last_name' => 'جدید',
            'system_role' => 'super_admin',
            'role' => 'salon_owner',
        ])->assertOk()->assertJsonPath('user.abilities.access_panel', false);
    }

    public function test_wrong_or_reused_code_is_rejected(): void
    {
        $this->requestCode('09123456789');
        $payload = [
            'mobile' => '09123456789',
            'code' => '2222',
            'mode' => 'register',
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
        ];

        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $error = ErrorLog::query()->sole();
        $this->assertSame(422, $error->status_code);
        $this->assertSame('/api/auth/otp/verify', $error->request_path);
        $this->assertSame('کد ورود صحیح نیست.', $error->message);
        $this->assertStringNotContainsString('2222', $error->message);

        $payload['code'] = '1111';
        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', $payload)->assertOk();
        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/logout')->assertOk();
        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_unknown_user_must_register_instead_of_using_login(): void
    {
        $this->requestCode('09123456789');

        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', [
            'mobile' => '09123456789', 'code' => '1111', 'mode' => 'login',
        ])->assertUnprocessable()->assertJsonValidationErrors('mobile');
    }

    public function test_authenticated_user_can_read_session_and_logout(): void
    {
        $user = User::factory()->create();
        $this->requestCode($user->mobile);
        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/verify', [
            'mobile' => $user->mobile, 'code' => '1111', 'mode' => 'login',
        ])->assertOk();

        $this->withHeaders($this->statefulHeaders)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/logout')->assertOk();
        $this->withHeaders($this->statefulHeaders)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_invalid_iranian_mobile_is_rejected(): void
    {
        $this->withHeaders($this->statefulHeaders)
            ->postJson('/api/auth/otp/request', ['mobile' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mobile');
    }

    public function test_authenticated_user_can_complete_first_login_onboarding(): void
    {
        $user = User::factory()->create(['is_first_login' => true]);

        $this->actingAs($user)
            ->postJson('/api/auth/onboarding/complete')
            ->assertOk()
            ->assertJsonPath('data.is_first_login', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_first_login' => false,
        ]);
    }

    public function test_guest_cannot_complete_first_login_onboarding(): void
    {
        $this->postJson('/api/auth/onboarding/complete')->assertUnauthorized();
    }

    public function test_authenticated_user_can_toggle_onboarding_state_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        $user = User::factory()->create(['is_first_login' => false]);

        $this->actingAs($user)
            ->putJson('/api/auth/onboarding/test-state', ['is_first_login' => true])
            ->assertOk()
            ->assertJsonPath('data.is_first_login', true)
            ->assertJsonPath('data.can_test_onboarding', true);

        $this->assertTrue($user->fresh()->is_first_login);
    }

    public function test_onboarding_test_state_endpoint_is_hidden_outside_debug_mode(): void
    {
        config(['app.debug' => false]);

        $this->actingAs(User::factory()->create())
            ->putJson('/api/auth/onboarding/test-state', ['is_first_login' => false])
            ->assertNotFound();
    }

    private function requestCode(string $mobile): OtpChallenge
    {
        $this->withHeaders($this->statefulHeaders)->postJson('/api/auth/otp/request', ['mobile' => $mobile])->assertOk();

        return OtpChallenge::query()->latest('id')->firstOrFail();
    }
}
