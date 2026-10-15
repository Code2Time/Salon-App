<?php

namespace Tests\Feature\Panel;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_only_store_own_non_overlapping_periods_inside_salon_hours(): void
    {
        [$owner, $employee, $salon, $membershipId] = $this->team();
        $day = $salon->workingDays()->create(['date' => '2026-10-15', 'is_open' => true]);
        $day->periods()->create(['starts_at' => '08:00', 'ends_at' => '12:00']);
        $day->periods()->create(['starts_at' => '16:00', 'ends_at' => '20:00', 'sort_order' => 1]);

        $this->actingAs($employee)->putJson('/api/panel/employee-schedule/days/2026-10-15', [
            'is_present' => true,
            'periods' => [['starts_at' => '08:00', 'ends_at' => '11:00'], ['starts_at' => '16:30', 'ends_at' => '19:00']],
        ])->assertOk();
        $this->assertDatabaseHas('employee_availability_days', ['salon_user_id' => $membershipId, 'is_present' => true]);

        $this->actingAs($employee)->putJson('/api/panel/employee-schedule/days/2026-10-15', [
            'is_present' => true, 'periods' => [['starts_at' => '11:00', 'ends_at' => '17:00']],
        ])->assertUnprocessable()->assertJsonValidationErrors('periods.0');

        $this->actingAs($employee)->putJson('/api/panel/employee-schedule/days/2026-10-15', [
            'membership_id' => $membershipId + 999, 'is_present' => false, 'periods' => [],
        ])->assertForbidden();
    }

    public function test_closed_day_and_overlapping_periods_are_rejected(): void
    {
        [, $employee, $salon] = $this->team();
        $salon->workingDays()->create(['date' => '2026-10-16', 'is_open' => false]);
        $this->actingAs($employee)->putJson('/api/panel/employee-schedule/days/2026-10-16', [
            'is_present' => true, 'periods' => [['starts_at' => '09:00', 'ends_at' => '10:00']],
        ])->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->actingAs($employee)->putJson('/api/panel/employee-schedule/days/2026-10-17', [
            'is_present' => true,
            'periods' => [['starts_at' => '09:00', 'ends_at' => '12:00'], ['starts_at' => '11:00', 'ends_at' => '13:00']],
        ])->assertUnprocessable()->assertJsonValidationErrors('periods');
    }

    public function test_owner_can_edit_employee_and_employee_receives_database_notification(): void
    {
        [$owner, $employee, , $membershipId] = $this->team();
        $this->actingAs($owner)->putJson('/api/panel/employee-schedule/days/2026-10-18', [
            'membership_id' => $membershipId,
            'is_present' => true,
            'periods' => [['starts_at' => '09:00', 'ends_at' => '14:00']],
        ])->assertOk();
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($employee)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'برنامه کاری شما ویرایش شد')
            ->assertJsonPath('data.0.date', '2026-10-18');
        $this->actingAs($employee)->postJson('/api/notifications/read-all')->assertOk();
        $this->assertNotNull($employee->notifications()->firstOrFail()->read_at);
        $this->actingAs($owner)->getJson('/api/panel/employee-schedule/employees')
            ->assertOk()->assertJsonPath('data.0.membership_id', $membershipId);
    }

    public function test_deleted_employees_are_ignored_in_management_filter(): void
    {
        [$owner, , $salon, $membershipId] = $this->team();
        $deletedEmployee = User::factory()->create();
        $salon->users()->attach($deletedEmployee, [
            'role' => SalonRole::Employee->value,
            'permissions' => ['appointments'],
            'is_active' => true,
        ]);
        $deletedEmployee->delete();

        $this->actingAs($owner)->getJson('/api/panel/employee-schedule/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.membership_id', $membershipId);
    }

    public function test_legacy_double_encoded_permissions_do_not_break_employee_schedule(): void
    {
        [, $employee, , $membershipId] = $this->team();
        DB::table('salon_user')->where('id', $membershipId)->update([
            'permissions' => json_encode(json_encode(['appointments'])),
        ]);

        $this->actingAs($employee)->getJson('/api/panel/employee-schedule?from=2026-10-15&to=2026-10-21')
            ->assertOk()
            ->assertJsonPath('data.employee.membership_id', $membershipId);
    }

    private function team(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara'.uniqid()]);
        $owner = User::factory()->create();
        $employee = User::factory()->create(['name' => 'مریم احمدی']);
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true]);
        $salon->users()->attach($employee, ['role' => SalonRole::Employee->value, 'permissions' => ['appointments'], 'is_active' => true]);
        $membershipId = $salon->users()->whereKey($employee->id)->firstOrFail()->pivot->id;

        return [$owner, $employee, $salon, $membershipId];
    }
}
