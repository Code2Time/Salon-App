<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleAppointment
{
    public function handle(Appointment $appointment, array $data): Appointment
    {
        try {
            return DB::transaction(function () use ($appointment, $data) {
                $timeline = (new AppointmentAvailability($appointment->salon, $data['date'], $data['items']))
                    ->assertTimeline($data['starts_at'], $appointment->id);
                $appointment->reservationSlots()->delete();
                $appointment->items()->delete();
                $appointment->update([
                    'starts_at' => $timeline[0]['starts_at']->utc(),
                    'ends_at' => $timeline[array_key_last($timeline)]['ends_at']->utc(),
                    'total_price' => collect($timeline)->sum(fn ($item) => $item['service']->price),
                ]);
                foreach ($timeline as $item) {
                    $appointment->items()->create([
                        'service_id' => $item['service']->id, 'salon_user_id' => $item['provider']->id,
                        'position' => $item['position'], 'service_name' => $item['service']->name, 'price' => $item['service']->price,
                        'duration_minutes' => $item['service']->duration_minutes, 'starts_at' => $item['starts_at']->utc(), 'ends_at' => $item['ends_at']->utc(),
                    ]);
                    for ($slot = $item['starts_at']; $slot->lt($item['ends_at']); $slot = $slot->addMinute()) {
                        $appointment->reservationSlots()->create(['salon_user_id' => $item['provider']->id, 'slot_at' => $slot->utc()]);
                    }
                }

                return $appointment->load(['salon.appointmentSetting', 'customer', 'items.provider.user']);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['starts_at' => 'زمان انتخابی دیگر آزاد نیست.']);
            }
            throw $exception;
        }
    }
}
