<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalonUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $this->owner = User::factory()->create();
        $this->salon->users()->attach($this->owner, ['role' => SalonRole::Owner->value]);
    }

    public function test_owner_can_create_and_list_an_employee_in_own_salon(): void
    {
        $this->actingAs($this->owner)->postJson('/api/panel/users', [
            'first_name' => 'مدیر',
            'last_name' => 'جدید',
            'mobile' => '09123456789',
            'email' => 'manager@example.com',
            'role' => SalonRole::Employee->value,
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('data.role', SalonRole::Employee->value)
            ->assertJsonPath('data.mobile', '09123456789');

        $this->actingAs($this->owner)->getJson('/api/panel/users?search=09123456789')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_employee_cannot_manage_panel_users(): void
    {
        $manager = User::factory()->create();
        $this->salon->users()->attach($manager, ['role' => SalonRole::Employee->value]);

        $this->actingAs($manager)->getJson('/api/panel/users')->assertForbidden();
    }

    public function test_owner_cannot_read_a_member_from_another_salon(): void
    {
        $otherSalon = Salon::create(['name' => 'دیگر', 'slug' => 'other']);
        $otherUser = User::factory()->create();
        $otherSalon->users()->attach($otherUser, ['role' => SalonRole::Employee->value]);

        $this->actingAs($this->owner)
            ->getJson("/api/panel/users/{$otherUser->id}")
            ->assertNotFound();
    }

    public function test_last_active_owner_cannot_be_deactivated_or_demoted(): void
    {
        $payload = [
            'first_name' => $this->owner->first_name,
            'last_name' => $this->owner->last_name,
            'email' => null,
            'role' => SalonRole::Employee->value,
            'is_active' => true,
        ];

        $this->actingAs($this->owner)
            ->putJson("/api/panel/users/{$this->owner->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->actingAs($this->owner)
            ->deleteJson("/api/panel/users/{$this->owner->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_delete_soft_deletes_user(): void
    {
        $manager = User::factory()->create();
        $this->salon->users()->attach($manager, ['role' => SalonRole::Employee->value]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/panel/users/{$manager->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $manager->id]);
        $this->assertDatabaseHas('salon_user', [
            'salon_id' => $this->salon->id,
            'user_id' => $manager->id,
        ]);
    }

    public function test_customer_membership_does_not_grant_panel_access(): void
    {
        $customer = User::factory()->create();
        $this->salon->users()->attach($customer, ['role' => SalonRole::Customer->value]);

        $this->actingAs($customer)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.abilities.access_panel', false)
            ->assertJsonPath('data.home_path', '/app');

        $this->actingAs($customer)->getJson('/api/panel/context')->assertForbidden();
    }

    public function test_users_can_be_filtered_by_customer_or_employee_role(): void
    {
        $customer = User::factory()->create();
        $employee = User::factory()->create();
        $this->salon->users()->attach($customer, ['role' => SalonRole::Customer->value]);
        $this->salon->users()->attach($employee, ['role' => SalonRole::Employee->value]);

        $this->actingAs($this->owner)->getJson('/api/panel/users?role=customer')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $customer->id);

        $this->actingAs($this->owner)->getJson('/api/panel/users?role=employee')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $employee->id);
    }

    public function test_owner_can_upload_user_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->owner)->post('/api/panel/users', [
            'first_name' => 'مشتری',
            'last_name' => 'تصویردار',
            'mobile' => '09123456789',
            'role' => SalonRole::Customer->value,
            'is_active' => true,
            'image' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.image_url', fn ($url) => str_starts_with($url, '/storage/users/'));
        $path = User::query()->where('mobile', '09123456789')->value('image');
        Storage::disk('public')->assertExists($path);
    }

    public function test_employee_menu_permissions_are_stored_and_returned_in_session(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/panel/users', [
            'first_name' => 'همکار',
            'last_name' => 'سالن',
            'mobile' => '09123456789',
            'role' => SalonRole::Employee->value,
            'is_active' => true,
            'permissions' => ['dashboard', 'services', 'appointments'],
        ])->assertCreated()
            ->assertJsonPath('data.permissions', ['dashboard', 'services', 'appointments']);

        $employee = User::query()->findOrFail($response->json('data.id'));
        $this->actingAs($employee)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.memberships.0.permissions', ['dashboard', 'services', 'appointments']);
    }

    public function test_invalid_employee_menu_permission_is_rejected(): void
    {
        $this->actingAs($this->owner)->postJson('/api/panel/users', [
            'first_name' => 'همکار',
            'last_name' => 'سالن',
            'mobile' => '09123456789',
            'role' => SalonRole::Employee->value,
            'is_active' => true,
            'permissions' => ['users'],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions.0');
    }

    public function test_user_model_supports_soft_delete(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::query()->find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }
}
