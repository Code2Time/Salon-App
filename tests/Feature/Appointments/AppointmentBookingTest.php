<?php

namespace Tests\Feature\Appointments;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_book_multiple_services_with_different_employees_and_snapshots(): void
    {
        [$salon, $customer, $items, $date] = $this->bookingFixture();
        $availability = $this->actingAs($customer)->postJson('/api/appointments/availability', ['date' => $date, 'items' => $items]);
        $availability->assertOk()->assertJsonFragment(['09:00']);

        $booking = $this->actingAs($customer)->postJson('/api/appointments', ['date' => $date, 'starts_at' => '09:00', 'items' => $items]);
        $booking->assertCreated()->assertJsonPath('data.status', 'confirmed')->assertJsonCount(2, 'data.items');
        $this->assertDatabaseHas('appointments', ['salon_id' => $salon->id, 'customer_id' => $customer->id, 'total_price' => 300000]);
        $this->assertDatabaseHas('appointment_items', ['position' => 0, 'service_name' => 'کوتاهی', 'price' => 100000]);
        $this->assertDatabaseHas('appointment_items', ['position' => 1, 'service_name' => 'رنگ', 'price' => 200000]);
        $this->assertDatabaseCount('appointment_reservation_slots', 90);
    }

    public function test_occupied_employee_and_invalid_provider_are_rejected(): void
    {
        [, $customer, $items, $date] = $this->bookingFixture();
        $this->actingAs($customer)->postJson('/api/appointments', ['date' => $date, 'starts_at' => '09:00', 'items' => $items])->assertCreated();
        $this->actingAs($customer)->postJson('/api/appointments', ['date' => $date, 'starts_at' => '09:00', 'items' => $items])
            ->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $invalid = $items;
        $invalid[0]['employee_membership_id'] = $items[1]['employee_membership_id'];
        $this->actingAs($customer)->postJson('/api/appointments/availability', ['date' => $date, 'items' => $invalid])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.employee_membership_id');
    }

    public function test_customer_cancellation_obeys_five_hour_rule_and_releases_slots(): void
    {
        CarbonImmutable::setTestNow('2026-10-15 08:00:00');
        [, $customer, $items] = $this->bookingFixture('2026-10-16');
        $appointment = $this->actingAs($customer)->postJson('/api/appointments', ['date' => '2026-10-16', 'starts_at' => '09:00', 'items' => $items])->json('data');
        $this->actingAs($customer)->postJson("/api/appointments/{$appointment['id']}/cancel", ['reason' => 'تغییر برنامه'])->assertOk();
        $this->assertDatabaseCount('appointment_reservation_slots', 0);

        $late = $this->actingAs($customer)->postJson('/api/appointments', ['date' => '2026-10-16', 'starts_at' => '09:00', 'items' => $items])->json('data');
        CarbonImmutable::setTestNow('2026-10-16 06:00:00');
        $this->actingAs($customer)->postJson("/api/appointments/{$late['id']}/cancel")->assertUnprocessable();
        CarbonImmutable::setTestNow();
    }

    public function test_manager_must_resolve_booking_before_changing_employee_schedule_and_can_reschedule(): void
    {
        [, $customer, $items, $date, $owner] = $this->bookingFixture();
        $appointment = $this->actingAs($customer)->postJson('/api/appointments', [
            'date' => $date, 'starts_at' => '09:00', 'items' => $items,
        ])->json('data');
        $this->actingAs($owner)->putJson("/api/panel/employee-schedule/days/{$date}", [
            'membership_id' => $items[0]['employee_membership_id'], 'is_present' => false, 'periods' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('appointments');

        $this->actingAs($owner)->putJson("/api/panel/appointments/{$appointment['id']}/reschedule", [
            'date' => $date, 'starts_at' => '10:00', 'items' => $items,
        ])->assertOk()->assertJsonPath('data.starts_at', "{$date} 10:00");
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $customer->id]);
    }

    public function test_dynamic_percentage_deposit_is_snapshotted_for_payment_phase(): void
    {
        [$salon, $customer, $items, $date] = $this->bookingFixture();
        $salon->appointmentSetting()->create([
            'planning_period' => 'weekly', 'is_recurring' => false, 'timezone' => 'Asia/Tehran',
            'deposit_enabled' => true, 'deposit_type' => 'percentage', 'deposit_value' => 25,
        ]);
        $this->actingAs($customer)->postJson('/api/appointments', [
            'date' => $date, 'starts_at' => '09:00', 'items' => $items,
        ])->assertCreated()->assertJsonPath('data.required_deposit_amount', 75000);
    }

    private function bookingFixture(?string $date = null): array
    {
        $date ??= now('Asia/Tehran')->addDays(3)->format('Y-m-d');
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $customer = User::factory()->create();
        $owner = User::factory()->create();
        $first = User::factory()->create(['name' => 'مریم']);
        $second = User::factory()->create(['name' => 'سارا']);
        foreach ([$first, $second] as $employee) {
            $salon->users()->attach($employee, ['role' => SalonRole::Employee->value, 'permissions' => ['appointments'], 'is_active' => true]);
        }
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true]);
        $firstId = $salon->users()->whereKey($first->id)->firstOrFail()->pivot->id;
        $secondId = $salon->users()->whereKey($second->id)->firstOrFail()->pivot->id;
        foreach ([$firstId, $secondId] as $memberId) {
            $membership = SalonUser::findOrFail($memberId);
            $day = $membership->availabilityDays()->create(['date' => $date, 'is_present' => true]);
            $day->periods()->create(['starts_at' => '09:00', 'ends_at' => '17:00']);
        }
        $cut = Service::create(['salon_id' => $salon->id, 'name' => 'کوتاهی', 'price' => 100000, 'duration_minutes' => 30, 'is_active' => true]);
        $color = Service::create(['salon_id' => $salon->id, 'name' => 'رنگ', 'price' => 200000, 'duration_minutes' => 60, 'is_active' => true]);
        $cut->providers()->attach($firstId);
        $color->providers()->attach($secondId);

        return [$salon, $customer, [['service_id' => $cut->id, 'employee_membership_id' => $firstId], ['service_id' => $color->id, 'employee_membership_id' => $secondId]], $date, $owner];
    }
}
