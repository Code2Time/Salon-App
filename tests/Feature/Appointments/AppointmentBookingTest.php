<?php

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelledByCustomer;
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
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', ['type' => AppointmentBooked::class]);
        foreach ($items as $item) {
            $employee = SalonUser::findOrFail($item['employee_membership_id'])->user;
            $this->assertSame($booking->json('data.id'), $employee->notifications()->sole()->data['appointment_id']);
        }
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
        [$salon, $customer, $items, , $owner] = $this->bookingFixture('2026-10-16');
        $appointment = $this->actingAs($customer)->postJson('/api/appointments', ['date' => '2026-10-16', 'starts_at' => '09:00', 'items' => $items])->json('data');
        $this->actingAs($customer)->postJson("/api/appointments/{$appointment['id']}/cancel", ['reason' => 'تغییر برنامه'])->assertOk();
        $this->assertDatabaseCount('appointment_reservation_slots', 0);
        $employeeIds = $salon->users()
            ->wherePivot('role', SalonRole::Employee->value)
            ->pluck('users.id');

        $this->assertDatabaseCount('notifications', 6);
        foreach ($employeeIds->push($owner->id) as $recipientId) {
            $this->assertDatabaseHas('notifications', [
                'notifiable_type' => User::class,
                'notifiable_id' => $recipientId,
            ]);
        }
        $notification = $owner->notifications()->where('type', AppointmentCancelledByCustomer::class)->sole();
        $this->assertSame('لغو نوبت توسط مشتری', $notification->data['title']);
        $this->assertSame('/panel/appointments', $notification->data['path']);
        $this->assertSame($appointment['id'], $notification->data['appointment_id']);
        $this->assertStringContainsString('تغییر برنامه', $notification->data['message']);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $customer->id]);

        $late = $this->actingAs($customer)->postJson('/api/appointments', ['date' => '2026-10-16', 'starts_at' => '09:00', 'items' => $items])->json('data');
        CarbonImmutable::setTestNow('2026-10-16 06:00:00');
        $this->actingAs($customer)->postJson("/api/appointments/{$late['id']}/cancel")->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 9);
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

    public function test_only_owner_can_delete_appointment_and_release_reserved_time(): void
    {
        [$salon, $customer, $items, $date, $owner] = $this->bookingFixture();
        $appointment = $this->actingAs($customer)->postJson('/api/appointments', [
            'date' => $date, 'starts_at' => '09:00', 'items' => $items,
        ])->json('data');
        $employee = $salon->users()->wherePivot('role', SalonRole::Employee->value)->firstOrFail();

        $this->actingAs($employee)
            ->deleteJson("/api/panel/appointments/{$appointment['id']}")
            ->assertForbidden();

        $this->actingAs($owner)
            ->deleteJson("/api/panel/appointments/{$appointment['id']}")
            ->assertOk()
            ->assertJsonPath('message', 'نوبت حذف شد.');

        $this->assertDatabaseMissing('appointments', ['id' => $appointment['id']]);
        $this->assertDatabaseCount('appointment_items', 0);
        $this->assertDatabaseCount('appointment_reservation_slots', 0);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $customer->id]);
    }

    public function test_past_confirmed_and_pending_appointments_are_automatically_expired(): void
    {
        CarbonImmutable::setTestNow('2026-10-18 12:00:00');
        [$salon, $customer] = $this->bookingFixture('2026-10-19');
        $pastConfirmed = $salon->appointments()->create([
            'customer_id' => $customer->id,
            'source' => 'panel',
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'total_price' => 100000,
            'required_deposit_amount' => 0,
        ]);
        $pastPending = $salon->appointments()->create([
            'customer_id' => $customer->id,
            'source' => 'customer',
            'status' => AppointmentStatus::Pending,
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHours(2),
            'total_price' => 100000,
            'required_deposit_amount' => 0,
        ]);
        $future = $salon->appointments()->create([
            'customer_id' => $customer->id,
            'source' => 'customer',
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'total_price' => 100000,
            'required_deposit_amount' => 0,
        ]);

        $this->actingAs($customer)->getJson('/api/appointments')->assertOk();

        $this->assertDatabaseHas('appointments', ['id' => $pastConfirmed->id, 'status' => AppointmentStatus::Expired->value]);
        $this->assertDatabaseHas('appointments', ['id' => $pastPending->id, 'status' => AppointmentStatus::Expired->value]);
        $this->assertDatabaseHas('appointments', ['id' => $future->id, 'status' => AppointmentStatus::Confirmed->value]);
        CarbonImmutable::setTestNow();
    }

    public function test_booking_notifies_only_owner_and_selected_employees_once_for_customer_and_panel(): void
    {
        [$salon, $customer, $items, $date, $owner] = $this->bookingFixture();
        $unrelated = User::factory()->create();
        $salon->users()->attach($unrelated, ['role' => 'employee', 'permissions' => ['appointments'], 'is_active' => true]);
        $salon->users()->attach($customer, ['role' => 'customer', 'is_active' => true]);
        $selected = SalonUser::findOrFail($items[0]['employee_membership_id'])->user;
        // Two services with the same specialist must generate one notification per booking.
        $repeated = [$items[0], $items[0]];
        foreach ([[$customer, '/api/appointments', '09:00'], [$owner, '/api/panel/appointments', '11:00']] as [$actor, $path, $time]) {
            $response = $this->actingAs($actor)->postJson($path, [
                'customer_id' => $customer->id, 'date' => $date, 'starts_at' => $time, 'items' => $repeated,
            ])->assertCreated();
            foreach ([$owner, $selected] as $recipient) {
                $notification = $recipient->notifications()->where('type', AppointmentBooked::class)
                    ->where('data->appointment_id', $response->json('data.id'))->sole();
                $this->assertSame('/panel/appointments', $notification->data['path']);
            }
        }
        $this->assertDatabaseCount('notifications', 4);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $unrelated->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $customer->id]);
    }

    public function test_employees_only_see_assigned_bookings_and_cannot_read_or_modify_other_bookings(): void
    {
        [$salon, $customer, $items, $date, $owner] = $this->bookingFixture();
        $ids = [];
        foreach ([$items, [$items[0]], [$items[1]]] as $index => $bookingItems) {
            $ids[] = $this->actingAs($customer)->postJson('/api/appointments', [
                'date' => $date, 'starts_at' => ['09:00', '11:00', '13:00'][$index], 'items' => $bookingItems,
            ])->assertCreated()->json('data.id');
        }
        $employee = SalonUser::findOrFail($items[0]['employee_membership_id'])->user;
        $salon->users()->updateExistingPivot($employee->id, ['permissions' => ['appointments', 'dashboard']]);
        // Owning a different salon must not grant broad access in this salon.
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other']);
        $other->users()->attach($employee, ['role' => 'salon_owner', 'is_active' => true]);
        $this->actingAs($employee)->getJson('/api/panel/appointments')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.*.id', fn ($visibleIds) => count(array_diff($visibleIds, [$ids[0], $ids[1]])) === 0);
        $this->getJson('/api/panel/appointments?date='.$date.'&status=confirmed')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/panel/appointments/options')->assertOk()->assertJsonPath('data.can_view_all', false);
        $this->getJson('/api/panel/appointments/'.$ids[0])->assertOk();
        $this->getJson('/api/panel/appointments/'.$ids[2])->assertNotFound();
        $this->putJson('/api/panel/appointments/'.$ids[2].'/status', ['status' => 'cancelled'])->assertNotFound();
        $this->putJson('/api/panel/appointments/'.$ids[2].'/reschedule', [
            'date' => $date, 'starts_at' => '15:00', 'items' => [$items[1]],
        ])->assertNotFound();
        $this->getJson('/api/panel/dashboard')->assertOk()->assertJsonCount(2, 'data.upcoming_appointments');
        $this->withHeader('X-Salon-ID', 999999)->getJson('/api/panel/appointments')->assertForbidden();
        $this->flushHeaders();
        $this->assertDatabaseHas('appointments', ['id' => $ids[2], 'status' => 'confirmed']);
        $unassigned = User::factory()->create();
        $salon->users()->attach($unassigned, ['role' => 'employee', 'permissions' => ['appointments'], 'is_active' => true]);
        $this->actingAs($unassigned)->getJson('/api/panel/appointments')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/panel/appointments/'.$ids[0])->assertNotFound();
        $this->actingAs($owner)->getJson('/api/panel/appointments')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/panel/appointments/options')->assertOk()->assertJsonPath('data.can_view_all', true);
        $superAdmin = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($superAdmin)->getJson('/api/panel/appointments')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/panel/appointments/'.$ids[2])->assertOk();
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
