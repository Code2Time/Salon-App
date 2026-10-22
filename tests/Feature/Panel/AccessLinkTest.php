<?php

namespace Tests\Feature\Panel;

use App\Models\Salon;
use App\Models\User;
use App\Services\LocalNetworkAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_gets_customer_link_with_current_network_host_and_port(): void
    {
        $owner = $this->member('salon_owner');
        config(['app.url' => 'http://192.168.1.10:8080']);
        $this->actingAs($owner)->getJson('http://192.168.1.22:8082/api/panel/access-link')
            ->assertOk()->assertJsonPath('data.url', 'http://192.168.1.22:8082/app')
            ->assertJsonPath('data.salon_name', 'سالن آرا');
        $this->getJson('https://salon.example.com/api/panel/access-link')
            ->assertOk()->assertJsonPath('data.url', 'https://salon.example.com/app');
    }

    public function test_localhost_uses_detected_network_ip_and_actual_server_port_without_npm(): void
    {
        $owner = $this->member('salon_owner');
        $this->mock(LocalNetworkAddress::class)->shouldReceive('detect')->andReturn('192.168.100.22');
        // A stale APP_URL must not redirect phones to a different IP or port.
        config(['app.url' => 'http://192.168.1.99:8080']);
        foreach (['localhost', '127.0.0.1', '[::1]'] as $host) {
            foreach ([8000, 8080, 8093] as $port) {
                $this->actingAs($owner)->getJson("http://{$host}:{$port}/api/panel/access-link")
                    ->assertOk()->assertJsonPath('data.url', "http://192.168.100.22:{$port}/app");
            }
        }
    }

    public function test_no_unusable_loopback_qr_is_offered_when_network_address_is_missing(): void
    {
        $this->mock(LocalNetworkAddress::class)->shouldReceive('detect')->andReturnNull();
        $owner = $this->member('salon_owner');
        foreach (['http://localhost:8080', 'http://127.0.0.1:8080', 'http://0.0.0.0:8080', 'http://[::1]:8080'] as $url) {
            config(['app.url' => $url]);
            $this->actingAs($owner)->getJson('http://localhost/api/panel/access-link')
                ->assertOk()->assertJsonPath('data.url', null);
        }
    }

    public function test_only_owner_and_super_admin_can_get_access_link(): void
    {
        $this->getJson('/api/panel/access-link')->assertUnauthorized();
        $employee = $this->member('employee');
        $this->actingAs($employee)->getJson('/api/panel/access-link')->assertForbidden();
        $customer = User::factory()->create();
        $this->actingAs($customer)->getJson('/api/panel/access-link')->assertForbidden();
        $admin = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($admin)->getJson('/api/panel/access-link')->assertOk();
    }

    private function member(string $role): User
    {
        $salon = Salon::firstOrCreate(['slug' => 'ara'], ['name' => 'سالن آرا', 'is_active' => true]);
        $user = User::factory()->create();
        $salon->users()->attach($user, ['role' => $role, 'is_active' => true]);

        return $user;
    }
}
