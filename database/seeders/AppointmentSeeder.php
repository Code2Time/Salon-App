<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Appointment;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\User;
use App\Services\Appointments\AppointmentAvailability;
use App\Services\Appointments\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $customer = User::query()->where('mobile', '09111111111')->firstOrFail();
        $creator = User::query()->where('mobile', '09333218766')->firstOrFail();
        $today = CarbonImmutable::now('Asia/Tehran')->toDateString();

        $employees = SalonUser::query()
            ->where('salon_id', $salon->id)
            ->where('role', SalonRole::Employee->value)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->whereIn('mobile', ['09222222222', '09333333333']))
            ->with(['user', 'services' => fn ($query) => $query->where('is_active', true)->orderBy('duration_minutes')])
            ->get();

        foreach ($employees as $employee) {
            $service = $employee->services->first();
            if (! $service) {
                continue;
            }

            $days = $employee->availabilityDays()
                ->where('is_present', true)
                ->whereDate('date', '>=', $today)
                ->orderBy('date')
                ->limit(3)
                ->get();

            $seededAppointments = 0;
            foreach ($days as $day) {
                if ($seededAppointments >= 2) {
                    break;
                }

                $date = $day->date->format('Y-m-d');
                $marker = "[seed:appointment:{$employee->id}:{$date}]";
                if (Appointment::query()->where('salon_id', $salon->id)->where('notes', $marker)->exists()) {
                    $seededAppointments++;

                    continue;
                }

                $items = [[
                    'service_id' => $service->id,
                    'employee_membership_id' => $employee->id,
                ]];
                $starts = (new AppointmentAvailability($salon, $date, $items))->availableStarts();
                $start = collect($starts)->first(fn (string $time) => $time >= '10:00') ?? ($starts[0] ?? null);
                if (! $start) {
                    continue;
                }

                app(BookAppointment::class)->create($salon, $customer, $creator, [
                    'date' => $date,
                    'starts_at' => $start,
                    'items' => $items,
                    'notes' => $marker,
                ], 'panel');
                $seededAppointments++;
            }
        }
    }
}
