<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_filter_and_update_a_category_in_own_salon(): void
    {
        Storage::fake('public');
        [$owner, $salon] = $this->ownerWithSalon();

        $create = $this->actingAs($owner)->post('/api/panel/service-categories', [
            'name' => 'مو',
            'description' => 'خدمات تخصصی مو',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 2,
            'image' => UploadedFile::fake()->createWithContent(
                'hair.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
        ], ['Accept' => 'application/json']);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'مو')
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.services_count', 0);
        $category = ServiceCategory::query()->sole();
        $this->assertSame($salon->id, $category->salon_id);
        Storage::disk('public')->assertExists($category->image);

        $this->actingAs($owner)->getJson('/api/panel/service-categories?featured=1')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($owner)->postJson("/api/panel/service-categories/{$category->id}", [
            'name' => 'مراقبت مو',
            'description' => null,
            'is_featured' => false,
            'is_active' => false,
            'sort_order' => 4,
        ])->assertOk()->assertJsonPath('data.name', 'مراقبت مو');
    }

    public function test_categories_are_tenant_scoped_and_name_is_unique_inside_active_salon_records(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        [$otherOwner, $otherSalon] = $this->ownerWithSalon('دیگر');
        $category = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'پوست']);
        ServiceCategory::create(['salon_id' => $otherSalon->id, 'name' => 'پوست']);

        $this->actingAs($owner)->getJson('/api/panel/service-categories')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($otherOwner)->getJson("/api/panel/service-categories/{$category->id}")
            ->assertNotFound();
        $this->actingAs($owner)->postJson('/api/panel/service-categories', ['name' => 'پوست'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_employee_view_and_manage_permissions_are_separate(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $viewer = User::factory()->create();
        $manager = User::factory()->create();
        $salon->users()->attach($viewer, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['service_categories'],
            'is_active' => true,
        ]);
        $salon->users()->attach($manager, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['service_categories', 'manage_service_categories'],
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->getJson('/api/panel/service-categories')->assertOk();
        $this->actingAs($viewer)->postJson('/api/panel/service-categories', ['name' => 'ناخن'])->assertForbidden();
        $this->actingAs($manager)->postJson('/api/panel/service-categories', ['name' => 'ناخن'])
            ->assertCreated();
    }

    public function test_deletion_detaches_shared_services_and_soft_deletes_only_orphans(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        $bride = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'عروس']);
        $makeup = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'میکاپ']);
        $shared = Service::create([
            'salon_id' => $salon->id, 'name' => 'میکاپ عروس', 'price' => 1000,
            'duration_minutes' => 90,
        ]);
        $orphan = Service::create([
            'salon_id' => $salon->id, 'name' => 'پکیج اختصاصی عروس', 'price' => 2000,
            'duration_minutes' => 120,
        ]);
        $bride->services()->attach([$shared->id, $orphan->id]);
        $makeup->services()->attach($shared->id);

        $this->actingAs($owner)->getJson("/api/panel/service-categories/{$bride->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.services')
            ->assertJsonFragment([
                'name' => 'میکاپ عروس',
                'duration_minutes' => 90,
                'is_active' => true,
            ]);

        $this->actingAs($owner)->getJson("/api/panel/service-categories/{$bride->id}/deletion-impact")
            ->assertOk()
            ->assertJsonFragment(['name' => 'میکاپ عروس', 'will_be_deleted' => false])
            ->assertJsonFragment(['name' => 'پکیج اختصاصی عروس', 'will_be_deleted' => true]);

        $this->actingAs($owner)->deleteJson("/api/panel/service-categories/{$bride->id}")
            ->assertOk()
            ->assertJsonFragment(['پکیج اختصاصی عروس']);

        $this->assertSoftDeleted('service_categories', ['id' => $bride->id]);
        $this->assertSoftDeleted('services', ['id' => $orphan->id]);
        $this->assertNotSoftDeleted('services', ['id' => $shared->id]);
        $this->assertDatabaseHas('service_category_service', [
            'service_category_id' => $makeup->id,
            'service_id' => $shared->id,
        ]);
    }

    /** @return array{User, Salon} */
    private function ownerWithSalon(string $suffix = ''): array
    {
        $salon = Salon::create(['name' => 'آرا'.$suffix, 'slug' => 'ara-'.($suffix ?: 'main').uniqid()]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, [
            'role' => SalonRole::Owner->value,
            'permissions' => null,
            'is_active' => true,
        ]);

        return [$owner, $salon];
    }
}
