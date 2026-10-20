<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Models\Appointment;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookAppointment
{
    public function create(Salon $salon, User $customer, User $creator, array $data, string $source): Appointment
    {
        $timeline = (new AppointmentAvailability($salon, $data['date'], $data['items']))->assertTimeline($data['starts_at']);
        try {
            return DB::transaction(function () use ($salon, $customer, $creator, $data, $source, $timeline) {
                $total = collect($timeline)->sum(fn ($item) => $item['service']->price);
                $settings = $salon->appointmentSetting;
                $deposit = $this->deposit($total, $settings);
                $appointment = $salon->appointments()->create([
                    'customer_id' => $customer->id, 'created_by_id' => $creator->id, 'source' => $source,
                    'status' => AppointmentStatus::Confirmed, 'starts_at' => $timeline[0]['starts_at']->utc(),
                    'ends_at' => $timeline[array_key_last($timeline)]['ends_at']->utc(), 'total_price' => $total,
                    'required_deposit_amount' => $deposit, 'notes' => $data['notes'] ?? null,
                ]);
                foreach ($timeline as $item) {
                    $appointment->items()->create([
                        'service_id' => $item['service']->id, 'salon_user_id' => $item['provider']->id,
                        'position' => $item['position'], 'service_name' => $item['service']->name,
                        'price' => $item['service']->price, 'duration_minutes' => $item['service']->duration_minutes,
                        'starts_at' => $item['starts_at']->utc(), 'ends_at' => $item['ends_at']->utc(),
                    ]);
                    for ($slot = $item['starts_at']; $slot->lt($item['ends_at']); $slot = $slot->addMinute()) {
                        $appointment->reservationSlots()->create(['salon_user_id' => $item['provider']->id, 'slot_at' => $slot->utc()]);
                    }
                }

                $appointment->load(['customer', 'items.provider.user']);
                $message = $customer->name.' نوبت جدید ثبت کرد. زمان: '
                    .$appointment->starts_at->setTimezone($settings?->timezone ?? 'Asia/Tehran')->format('Y-m-d H:i')
                    .'؛ خدمات: '.$appointment->items->pluck('service_name')->implode('، ');
                $salon->users()->wherePivot('role', SalonRole::Owner->value)
                    ->wherePivot('is_active', true)->where('users.is_active', true)->get()
                    ->each->notify(new AppointmentBooked($salon->id, $appointment->id, $message));

                return $appointment->load(['customer', 'items.provider.user']);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['starts_at' => 'این زمان لحظاتی قبل رزرو شد؛ زمان دیگری انتخاب کنید.']);
            }
            throw $exception;
        }
    }

    private function deposit(int $total, mixed $settings): int
    {
        if (! $settings?->deposit_enabled) {
            return 0;
        }

        return $settings->deposit_type === 'percentage'
            ? (int) ceil($total * min(100, $settings->deposit_value ?? 0) / 100)
            : min($total, $settings->deposit_value ?? 0);
    }
}
