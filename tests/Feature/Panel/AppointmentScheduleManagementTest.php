<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\AppointmentSetting;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_configure_period_and_store_open_and_special_days(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();

        $this->actingAs($owner)->getJson('/api/panel/appointment-schedule?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertJsonPath('data.settings.planning_period', 'weekly')
            ->assertJsonPath('data.settings.is_recurring', false)
            ->assertJsonCount(0, 'data.days');

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/settings', [
            'planning_period' => 'monthly',
            'is_recurring' => true,
        ])->assertOk()
            ->assertJsonPath('data.planning_period', 'monthly')
            ->assertJsonPath('data.is_recurring', true);

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-14', [
            'is_open' => true,
            'title' => 'ساعت کاری ویژه',
            'description' => 'دو شیفت کاری',
            'periods' => [
                ['starts_at' => '08:00', 'ends_at' => '12:00'],
                ['starts_at' => '16:00', 'ends_at' => '20:00'],
            ],
        ])->assertOk()
            ->assertJsonPath('data.date', '2026-10-14')
            ->assertJsonPath('data.is_open', true)
            ->assertJsonCount(2, 'data.periods');

        $this->assertDatabaseHas('appointment_settings', [
            'salon_id' => $salon->id, 'planning_period' => 'monthly', 'is_recurring' => true,
        ]);
        $this->assertDatabaseHas('salon_working_days', [
            'salon_id' => $salon->id, 'date' => '2026-10-14', 'is_open' => true,
        ]);

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-15', [
            'is_open' => false,
            'title' => 'تعطیل رسمی',
            'description' => null,
            'periods' => [],
        ])->assertOk()->assertJsonPath('data.title', 'تعطیل رسمی');
    }

    public function test_invalid_or_overlapping_periods_are_rejected(): void
    {
        [$owner] = $this->ownerWithSalon();

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-14', [
            'is_open' => true, 'periods' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('periods');

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-14', [
            'is_open' => true,
            'periods' => [
                ['starts_at' => '08:00', 'ends_at' => '12:00'],
                ['starts_at' => '11:30', 'ends_at' => '14:00'],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('periods');

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-14', [
            'is_open' => true,
            'periods' => [['starts_at' => '18:00', 'ends_at' => '09:00']],
        ])->assertUnprocessable()->assertJsonValidationErrors('periods.0.ends_at');
    }

    public function test_closing_day_requires_confirmation_and_notifies_scheduled_employees(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        $employee = User::factory()->create(['name' => 'کارمند برنامه‌دار']);
        $salon->users()->attach($employee, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['appointments'],
            'is_active' => true,
        ]);
        $membership = SalonUser::query()->where('salon_id', $salon->id)->where('user_id', $employee->id)->firstOrFail();
        $employeeDay = $membership->availabilityDays()->create(['date' => '2026-10-20', 'is_present' => true]);
        $employeeDay->periods()->create(['starts_at' => '09:00', 'ends_at' => '17:00']);
        $payload = ['is_open' => false, 'title' => 'تعطیل', 'description' => null, 'periods' => []];

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-20', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'employee_schedule_conflict')
            ->assertJsonPath('employees.0.name', 'کارمند برنامه‌دار');
        $this->assertDatabaseHas('employee_availability_days', ['id' => $employeeDay->id, 'is_present' => true]);
        $this->assertDatabaseCount('notifications', 0);

        $this->actingAs($owner)->putJson('/api/panel/appointment-schedule/days/2026-10-20', [
            ...$payload,
            'confirm_employee_impact' => true,
        ])->assertOk()->assertJsonPath('data.is_open', false);

        $this->assertDatabaseHas('employee_availability_days', ['id' => $employeeDay->id, 'is_present' => false]);
        $this->assertDatabaseMissing('employee_availability_periods', ['employee_availability_day_id' => $employeeDay->id]);
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($employee)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'روز کاری شما لغو شد')
            ->assertJsonPath('data.0.date', '2026-10-20');
    }

    public function test_employee_cannot_read_or_change_appointment_settings(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $employee = User::factory()->create();
        $salon->users()->attach($employee, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['appointments'],
            'is_active' => true,
        ]);

        $this->actingAs($employee)->getJson('/api/panel/appointment-schedule?from=2026-10-01&to=2026-10-07')
            ->assertForbidden();
        $this->actingAs($employee)->putJson('/api/panel/appointment-schedule/settings', [
            'planning_period' => 'weekly', 'is_recurring' => false,
        ])->assertForbidden();
    }

    public function test_schedule_data_is_isolated_by_salon_context(): void
    {
        [$owner, $salon] = $this->ownerWithSalon();
        [$otherOwner, $otherSalon] = $this->ownerWithSalon('دیگر');
        AppointmentSetting::create([
            'salon_id' => $otherSalon->id,
            'planning_period' => 'monthly',
            'is_recurring' => true,
            'timezone' => 'Asia/Tehran',
        ]);
        $otherSalon->workingDays()->create([
            'date' => '2026-10-14', 'is_open' => false, 'title' => 'خصوصی سالن دیگر',
        ]);

        $this->actingAs($owner)->getJson('/api/panel/appointment-schedule?from=2026-10-01&to=2026-10-31')
            ->assertOk()->assertJsonMissing(['title' => 'خصوصی سالن دیگر']);
        $this->actingAs($owner)->withHeader('X-Salon-ID', (string) $otherSalon->id)
            ->getJson('/api/panel/appointment-schedule?from=2026-10-01&to=2026-10-31')
            ->assertForbidden();
        $this->actingAs($otherOwner)->getJson('/api/panel/appointment-schedule?from=2026-10-01&to=2026-10-31')
            ->assertOk()->assertJsonPath('data.days.0.title', 'خصوصی سالن دیگر');
        $this->assertNotSame($salon->id, $otherSalon->id);
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
