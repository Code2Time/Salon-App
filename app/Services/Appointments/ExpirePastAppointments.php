<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class ExpirePastAppointments
{
    public function handle(?int $salonId = null, ?int $customerId = null): int
    {
        return Appointment::query()
            ->when($salonId, fn ($query) => $query->where('salon_id', $salonId))
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->where('ends_at', '<=', now())
            ->update(['status' => AppointmentStatus::Expired->value]);
    }
}
