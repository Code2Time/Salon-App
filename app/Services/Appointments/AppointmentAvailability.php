<?php

namespace App\Services\Appointments;

use App\Models\AppointmentReservationSlot;
use App\Models\EmployeeAvailabilityDay;
use App\Models\Salon;
use App\Models\SalonUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentAvailability
{
    public function __construct(private readonly Salon $salon, private readonly string $date, private readonly array $requestedItems) {}

    public function availableStarts(?int $exceptAppointmentId = null): array
    {
        $items = $this->validatedItems();
        $interval = $this->salon->appointmentSetting?->slot_interval_minutes ?? 15;
        $starts = [];
        for ($minute = 0; $minute < 1440; $minute += $interval) {
            $start = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
            try {
                $this->timeline($items, $start, $exceptAppointmentId);
                $starts[] = $start;
            } catch (ValidationException) {
                // This candidate is not available.
            }
        }

        return $starts;
    }

    public function assertTimeline(string $start, ?int $exceptAppointmentId = null): array
    {
        return $this->timeline($this->validatedItems(), $start, $exceptAppointmentId);
    }

    private function validatedItems(): Collection
    {
        if (count($this->requestedItems) < 1 || count($this->requestedItems) > 10) {
            throw ValidationException::withMessages(['items' => 'بین یک تا ده سرویس انتخاب کنید.']);
        }

        return collect($this->requestedItems)->values()->map(function (array $item, int $position) {
            $service = $this->salon->services()->whereKey($item['service_id'])->where('is_active', true)->first();
            $provider = SalonUser::query()->whereKey($item['employee_membership_id'])
                ->where('salon_id', $this->salon->id)->where('role', 'employee')->where('is_active', true)->first();
            if (! $service) {
                throw ValidationException::withMessages(["items.{$position}.service_id" => 'سرویس معتبر نیست.']);
            }
            if (! $provider || ! $service->providers()->whereKey($provider->id)->exists()) {
                throw ValidationException::withMessages(["items.{$position}.employee_membership_id" => 'این کارمند ارائه‌دهنده سرویس انتخابی نیست.']);
            }

            return ['service' => $service, 'provider' => $provider, 'position' => $position];
        });
    }

    private function timeline(Collection $items, string $start, ?int $exceptAppointmentId): array
    {
        $timezone = $this->salon->appointmentSetting?->timezone ?? 'Asia/Tehran';
        $cursor = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$this->date} {$start}", $timezone);
        if (! $cursor || $cursor->format('Y-m-d H:i') !== "{$this->date} {$start}") {
            throw ValidationException::withMessages(['starts_at' => 'زمان شروع معتبر نیست.']);
        }
        if ($cursor->lte(now())) {
            throw ValidationException::withMessages(['starts_at' => 'زمان شروع باید در آینده باشد.']);
        }
        $timeline = [];
        foreach ($items as $item) {
            $end = $cursor->addMinutes($item['service']->duration_minutes);
            if ($end->format('Y-m-d') !== $this->date) {
                throw ValidationException::withMessages(['starts_at' => 'نوبت باید در همان روز پایان یابد.']);
            }
            $this->assertEmployeePresent($item['provider'], $cursor, $end);
            $this->assertNotReserved($item['provider']->id, $cursor, $end, $exceptAppointmentId);
            $timeline[] = [...$item, 'starts_at' => $cursor, 'ends_at' => $end];
            $cursor = $end;
        }

        return $timeline;
    }

    private function assertEmployeePresent(SalonUser $provider, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $salonDay = $this->salon->workingDays()->with('periods')->whereDate('date', $this->date)->first();
        if ($salonDay && ! $salonDay->is_open) {
            throw ValidationException::withMessages(['date' => 'سالن در این روز تعطیل است.']);
        }
        $salonPeriods = $salonDay?->periods ?? collect([(object) ['starts_at' => '09:00', 'ends_at' => '17:00']]);
        if (! $this->contained($salonPeriods, $start, $end)) {
            throw ValidationException::withMessages(['starts_at' => 'زمان خارج از ساعات کاری سالن است.']);
        }
        $attendance = EmployeeAvailabilityDay::query()->where('salon_user_id', $provider->id)->whereDate('date', $this->date)->with('periods')->first();
        if (! $attendance?->is_present || ! $this->contained($attendance->periods, $start, $end)) {
            throw ValidationException::withMessages(['starts_at' => 'کارمند در این بازه حضور ندارد.']);
        }
    }

    private function contained(Collection $periods, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $periods->contains(fn ($period) => $start->format('H:i') >= substr($period->starts_at, 0, 5) && $end->format('H:i') <= substr($period->ends_at, 0, 5));
    }

    private function assertNotReserved(int $membershipId, CarbonImmutable $start, CarbonImmutable $end, ?int $exceptAppointmentId): void
    {
        $query = AppointmentReservationSlot::query()->where('salon_user_id', $membershipId)
            ->where('slot_at', '>=', $start->utc())->where('slot_at', '<', $end->utc());
        if ($exceptAppointmentId) {
            $query->where('appointment_id', '!=', $exceptAppointmentId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'این زمان دیگر آزاد نیست.']);
        }
    }
}
