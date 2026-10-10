<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_only_update_their_own_profile(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create();
        $anotherUser = User::factory()->create(['email' => 'another@example.com']);

        $response = $this->actingAs($customer)->post('/api/profile', [
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
            'email' => 'maryam@example.com',
            'birth_date' => '1994-08-23',
            'image' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'مریم احمدی')
            ->assertJsonPath('data.email', 'maryam@example.com')
            ->assertJsonPath('data.birth_date', '1994-08-23');

        $customer->refresh();
        Storage::disk('public')->assertExists($customer->image);
        $this->assertSame('مریم', $customer->first_name);
        $this->assertSame('another@example.com', $anotherUser->fresh()->email);
    }

    public function test_guest_cannot_update_customer_profile(): void
    {
        $this->postJson('/api/profile', [
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
        ])->assertUnauthorized();
    }

    public function test_customer_profile_rejects_duplicate_email_and_future_birth_date(): void
    {
        User::factory()->create(['email' => 'used@example.com']);
        $customer = User::factory()->create();

        $this->actingAs($customer)->postJson('/api/profile', [
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
            'email' => 'used@example.com',
            'birth_date' => now()->addDay()->format('Y-m-d'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'birth_date']);
    }
}
