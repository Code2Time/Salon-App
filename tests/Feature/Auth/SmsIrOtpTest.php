<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsIrOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'otp.driver' => 'smsir_sandbox',
            'otp.fixed_code' => '2244',
            'otp.smsir.api_key' => 'sandbox-test-key',
            'otp.smsir.template_id' => 12345,
            'otp.smsir.parameter_name' => 'Code',
        ]);
        Http::preventStrayRequests();
        $this->withHeaders([
            'Origin' => 'http://localhost:8000',
            'Referer' => 'http://localhost:8000/login',
        ]);
    }

    public function test_sandbox_sends_code_and_allows_login_with_2244(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 1])]);
        $user = User::factory()->create();

        $this->postJson('/api/auth/otp/request', ['mobile' => $user->mobile])->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sms.ir/v1/send/verify'
            && $request->hasHeader('X-API-KEY', 'sandbox-test-key')
            && $request['mobile'] === $user->mobile
            && $request['templateId'] === 12345
            && $request['parameters'] === [['name' => 'Code', 'value' => '2244']]);

        $this->postJson('/api/auth/otp/verify', [
            'mobile' => $user->mobile, 'code' => '1111', 'mode' => 'login',
        ])->assertUnprocessable();
        $this->postJson('/api/auth/otp/verify', [
            'mobile' => $user->mobile, 'code' => '2244', 'mode' => 'login',
        ])->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_delivery_deletes_challenge_and_hides_provider_response(): void
    {
        Http::fake(['api.sms.ir/*' => Http::response(['status' => 10, 'message' => 'private-provider-detail'])]);

        $this->postJson('/api/auth/otp/request', ['mobile' => '09123456789'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'ارسال کد ورود ناموفق بود؛ دوباره تلاش کنید.')
            ->assertDontSee('private-provider-detail');
        $this->assertDatabaseCount('otp_challenges', 0);
    }

    public function test_missing_template_fails_before_contacting_provider(): void
    {
        config(['otp.smsir.template_id' => 0]);
        $this->postJson('/api/auth/otp/request', ['mobile' => '09123456789'])->assertStatus(503);
        Http::assertNothingSent();
        $this->assertDatabaseCount('otp_challenges', 0);
    }

    public function test_connection_failure_is_reported_without_transport_details(): void
    {
        Http::fake(['api.sms.ir/*' => Http::failedConnection()]);
        $this->postJson('/api/auth/otp/request', ['mobile' => '09123456789'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'ارتباط با سرویس پیامک برقرار نشد؛ دوباره تلاش کنید.');
        $this->assertDatabaseCount('otp_challenges', 0);
    }
}
