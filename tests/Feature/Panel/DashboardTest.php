<?php

namespace Tests\Feature\Panel;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_sees_real_salon_scoped_dashboard_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-18 09:00:00', 'Asia/Tehran'));
        [$owner, $salon] = $this->ownerWithSalon('اصلی');
        $customer = User::factory()->create(['name' => 'مشتری امروز', 'mobile' => '09120000001']);
        $employee = User::factory()->create(['name' => 'متخصص امروز']);
        $salon->users()->attach($customer, ['role' => SalonRole::Customer->value, 'is_active' => true]);
        $salon->users()->attach($employee, ['role' => SalonRole::Employee->value, 'permissions' => ['dashboard'], 'is_active' => true]);
        $membership = SalonUser::query()->where('salon_id', $salon->id)->where('user_id', $employee->id)->firstOrFail();
        $day = $membership->availabilityDays()->create(['date' => '2026-10-18', 'is_present' => true]);
        $day->periods()->create(['starts_at' => '09:00', 'ends_at' => '17:00']);
        $service = $salon->services()->create(['name' => 'اصلاح مو', 'price' => 300000, 'duration_minutes' => 30, 'is_active' => true]);

        $appointment = $salon->appointments()->create([
            'customer_id' => $customer->id,
            'created_by_id' => $owner->id,
            'source' => 'panel',
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => CarbonImmutable::parse('2026-10-18 10:00', 'Asia/Tehran')->utc(),
            'ends_at' => CarbonImmutable::parse('2026-10-18 10:30', 'Asia/Tehran')->utc(),
            'total_price' => 300000,
            'required_deposit_amount' => 0,
        ]);
        $appointment->items()->create([
            'service_id' => $service->id,
            'salon_user_id' => $membership->id,
            'position' => 1,
            'service_name' => 'اصلاح مو',
            'price' => 300000,
            'duration_minutes' => 30,
            'starts_at' => $appointment->starts_at,
            'ends_at' => $appointment->ends_at,
        ]);

        [, $otherSalon] = $this->ownerWithSalon('دیگر');
        $otherSalon->services()->create(['name' => 'خصوصی', 'price' => 1, 'duration_minutes' => 5, 'is_active' => true]);

        $this->actingAs($owner)->getJson('/api/panel/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.today_appointments', 1)
            ->assertJsonPath('data.summary.today_confirmed', 1)
            ->assertJsonPath('data.summary.active_customers', 1)
            ->assertJsonPath('data.summary.active_employees', 1)
            ->assertJsonPath('data.summary.active_services', 1)
            ->assertJsonPath('data.upcoming_appointments.0.customer.name', 'مشتری امروز')
            ->assertJsonPath('data.upcoming_appointments.0.services.0', 'اصلاح مو')
            ->assertJsonPath('data.employees.0.schedule_status', 'present')
            ->assertJsonPath('data.employees.0.periods.0.starts_at', '09:00');
    }

    public function test_employee_needs_dashboard_permission(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'dashboard-permission']);
        $allowed = User::factory()->create();
        $denied = User::factory()->create();
        $salon->users()->attach($allowed, ['role' => SalonRole::Employee->value, 'permissions' => ['dashboard'], 'is_active' => true]);
        $salon->users()->attach($denied, ['role' => SalonRole::Employee->value, 'permissions' => ['appointments'], 'is_active' => true]);

        $this->actingAs($allowed)->getJson('/api/panel/dashboard')->assertOk();
        $this->actingAs($denied)->getJson('/api/panel/dashboard')->assertForbidden();
    }

    public function test_guest_cannot_read_dashboard(): void
    {
        $this->getJson('/api/panel/dashboard')->assertUnauthorized();
    }

    /** @return array{User, Salon} */
    private function ownerWithSalon(string $suffix): array
    {
        $salon = Salon::create(['name' => 'آرا '.$suffix, 'slug' => 'dashboard-'.uniqid()]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'permissions' => null, 'is_active' => true]);

        return [$owner, $salon];
    }
}
