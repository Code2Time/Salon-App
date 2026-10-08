<?php

namespace Tests\Feature\Auth;

use App\Enums\SalonRole;
use App\Enums\SystemRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndSalonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_panel_api(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/panel/context')
            ->assertForbidden();
    }

    public function test_salon_member_can_only_use_own_salon_context(): void
    {
        [$ownSalon, $otherSalon] = [
            Salon::create(['name' => 'آرا', 'slug' => 'ara']),
            Salon::create(['name' => 'سالن دیگر', 'slug' => 'other']),
        ];
        $manager = User::factory()->create();
        $ownSalon->users()->attach($manager, ['role' => SalonRole::Manager->value]);

        $this->actingAs($manager)
            ->withHeader('X-Salon-ID', (string) $ownSalon->id)
            ->getJson('/api/panel/context')
            ->assertOk()
            ->assertJsonPath('data.salon_id', $ownSalon->id);

        $this->actingAs($manager)
            ->withHeader('X-Salon-ID', (string) $otherSalon->id)
            ->getJson('/api/panel/context')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_every_salon(): void
    {
        $salon = Salon::create(['name' => 'سالن دوم', 'slug' => 'second']);
        $superAdmin = User::factory()->create(['system_role' => SystemRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->withHeader('X-Salon-ID', (string) $salon->id)
            ->getJson('/api/panel/context')
            ->assertOk()
            ->assertJsonPath('data.salon_id', $salon->id);
    }

    public function test_super_admin_can_access_both_layouts_and_receives_image_url(): void
    {
        $superAdmin = User::factory()->create([
            'system_role' => SystemRole::SuperAdmin,
            'image' => 'users/avatar.jpg',
        ]);

        $this->actingAs($superAdmin)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.image_url', '/storage/users/avatar.jpg')
            ->assertJsonPath('data.abilities.access_panel', true)
            ->assertJsonPath('data.abilities.access_customer_app', true)
            ->assertJsonPath('data.home_path', '/panel');
    }

    public function test_inactive_membership_does_not_grant_panel_access(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $employee = User::factory()->create();
        $salon->users()->attach($employee, ['role' => SalonRole::Employee->value, 'is_active' => false]);

        $this->actingAs($employee)->getJson('/api/panel/context')->assertForbidden();
    }
}
