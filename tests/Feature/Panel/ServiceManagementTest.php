<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_filter_update_and_soft_delete_service(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        $hair = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'مو']);
        $bride = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'عروس']);
        $employee = User::factory()->create(['name' => 'مریم احمدی']);
        $salon->users()->attach($employee, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['services'],
            'is_active' => true,
        ]);
        $membershipId = $salon->users()->whereKey($employee->id)->firstOrFail()->pivot->id;

        $create = $this->actingAs($owner)->postJson('/api/panel/services', [
            'name' => 'میکاپ عروس',
            'description' => 'میکاپ کامل',
            'price' => 2500000,
            'duration_minutes' => 90,
            'is_active' => true,
            'category_ids' => [$hair->id, $bride->id],
            'provider_membership_ids' => [$membershipId],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'میکاپ عروس')
            ->assertJsonPath('data.price', 2500000)
            ->assertJsonCount(2, 'data.categories')
            ->assertJsonCount(1, 'data.providers');
        $service = Service::query()->sole();

        $this->actingAs($owner)->getJson("/api/panel/services?category_id={$bride->id}&provider_membership_id={$membershipId}&status=active")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($owner)->putJson("/api/panel/services/{$service->id}", [
            'name' => 'میکاپ ویژه عروس',
            'description' => null,
            'price' => 3000000,
            'duration_minutes' => 120,
            'is_active' => false,
            'category_ids' => [$bride->id],
            'provider_membership_ids' => [],
        ])->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(0, 'data.providers');

        $this->actingAs($owner)->deleteJson("/api/panel/services/{$service->id}")->assertOk();
        $this->assertSoftDeleted('services', ['id' => $service->id]);
        $this->assertDatabaseHas('service_category_service', [
            'service_id' => $service->id,
            'service_category_id' => $bride->id,
        ]);
    }

    public function test_service_relations_are_restricted_to_current_salon(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        [, $otherSalon] = $this->ownerWithSalon('دیگر');
        $category = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'مو']);
        $otherCategory = ServiceCategory::create(['salon_id' => $otherSalon->id, 'name' => 'ناخن']);
        $otherEmployee = User::factory()->create();
        $otherSalon->users()->attach($otherEmployee, [
            'role' => SalonRole::Employee->value, 'permissions' => [], 'is_active' => true,
        ]);
        $otherMembershipId = $otherSalon->users()->whereKey($otherEmployee->id)->firstOrFail()->pivot->id;

        $payload = [
            'name' => 'کوتاهی', 'price' => 100000, 'duration_minutes' => 30,
            'is_active' => true, 'category_ids' => [$category->id],
            'provider_membership_ids' => [$otherMembershipId],
        ];
        $this->actingAs($owner)->postJson('/api/panel/services', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('provider_membership_ids.0');

        $payload['category_ids'] = [$otherCategory->id];
        $payload['provider_membership_ids'] = [];
        $this->actingAs($owner)->postJson('/api/panel/services', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('category_ids.0');
    }

    public function test_employee_view_and_manage_permissions_are_separate(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $category = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'پوست']);
        $viewer = User::factory()->create();
        $manager = User::factory()->create();
        $salon->users()->attach($viewer, [
            'role' => SalonRole::Employee->value, 'permissions' => ['services'], 'is_active' => true,
        ]);
        $salon->users()->attach($manager, [
            'role' => SalonRole::Employee->value, 'permissions' => ['services', 'manage_services'], 'is_active' => true,
        ]);
        $payload = [
            'name' => 'فیشال', 'price' => 500000, 'duration_minutes' => 60,
            'is_active' => true, 'category_ids' => [$category->id], 'provider_membership_ids' => [],
        ];

        $this->actingAs($viewer)->getJson('/api/panel/services')->assertOk();
        $this->actingAs($viewer)->postJson('/api/panel/services', $payload)->assertForbidden();
        $this->actingAs($manager)->postJson('/api/panel/services', $payload)->assertCreated();
    }

    public function test_service_requires_valid_price_duration_and_category(): void
    {
        [$owner] = $this->ownerWithSalon();

        $this->actingAs($owner)->postJson('/api/panel/services', [
            'name' => 'نامعتبر', 'price' => -1, 'duration_minutes' => 2,
            'is_active' => true, 'category_ids' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['price', 'duration_minutes', 'category_ids']);
    }

    /** @return array{User, Salon} */
    private function ownerWithSalon(string $suffix = ''): array
    {
        $salon = Salon::create(['name' => 'آرا'.$suffix, 'slug' => 'ara-'.($suffix ?: 'main').uniqid()]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, [
            'role' => SalonRole::Owner->value, 'permissions' => null, 'is_active' => true,
        ]);

        return [$owner, $salon];
    }
}
