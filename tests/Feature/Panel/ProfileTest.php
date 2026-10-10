<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_user_can_update_own_profile_and_image(): void
    {
        Storage::fake('public');
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $employee = User::factory()->create();
        $salon->users()->attach($employee, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['dashboard'],
        ]);

        $response = $this->actingAs($employee)->post('/api/panel/profile', [
            'first_name' => 'نام جدید',
            'last_name' => 'کارمند',
            'email' => 'employee@example.com',
            'birth_date' => '1995-05-12',
            'image' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'نام جدید کارمند')
            ->assertJsonPath('data.birth_date', '1995-05-12')
            ->assertJsonPath('data.image_url', fn ($url) => str_starts_with($url, '/storage/users/'));

        $employee->refresh();
        Storage::disk('public')->assertExists($employee->image);
        $this->assertSame('employee@example.com', $employee->email);
    }

    public function test_customer_cannot_update_profile_through_panel_endpoint(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->postJson('/api/panel/profile', [
            'first_name' => 'مشتری',
            'last_name' => 'آزمایشی',
        ])->assertForbidden();
    }
}
