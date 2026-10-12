<?php

namespace Tests\Feature\Customer;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_only_active_services_categories_and_providers(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'is_active' => true]);
        $activeCategory = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'مو', 'is_active' => true]);
        $inactiveCategory = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'قدیمی', 'is_active' => false]);
        $employee = User::factory()->create(['name' => 'مریم احمدی', 'is_active' => true]);
        $salon->users()->attach($employee, [
            'role' => SalonRole::Employee->value, 'permissions' => [], 'is_active' => true,
        ]);
        $membershipId = $salon->users()->whereKey($employee->id)->firstOrFail()->pivot->id;
        $active = Service::create([
            'salon_id' => $salon->id, 'name' => 'کوتاهی', 'price' => 400000,
            'duration_minutes' => 45, 'is_active' => true,
        ]);
        $inactive = Service::create([
            'salon_id' => $salon->id, 'name' => 'سرویس غیرفعال', 'price' => 1,
            'duration_minutes' => 30, 'is_active' => false,
        ]);
        $active->categories()->attach($activeCategory);
        $active->providers()->attach($membershipId);
        $inactive->categories()->attach($activeCategory);
        Service::create([
            'salon_id' => $salon->id, 'name' => 'بدون دسته فعال', 'price' => 1,
            'duration_minutes' => 30, 'is_active' => true,
        ])->categories()->attach($inactiveCategory);

        $this->getJson('/api/catalog/services')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'کوتاهی')
            ->assertJsonPath('data.0.providers.0.name', 'مریم احمدی')
            ->assertJsonPath('meta.per_page', 5);

        $this->getJson('/api/catalog/service-categories')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'مو');
    }

    public function test_catalog_supports_category_search_sort_and_salon_scope(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'is_active' => true]);
        $category = ServiceCategory::create(['salon_id' => $salon->id, 'name' => 'ناخن']);
        foreach ([['مانیکور', 500000], ['کاشت ناخن', 900000]] as [$name, $price]) {
            $service = Service::create([
                'salon_id' => $salon->id, 'name' => $name, 'price' => $price,
                'duration_minutes' => 60, 'is_active' => true,
            ]);
            $service->categories()->attach($category);
        }

        $this->getJson("/api/catalog/services?category_id={$category->id}&search=ناخن&sort=price_desc")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'کاشت ناخن');
        $this->getJson('/api/catalog/services?salon=missing')->assertNotFound();
    }
}
