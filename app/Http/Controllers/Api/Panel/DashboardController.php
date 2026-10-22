<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Models\SalonUser;
use App\Services\Appointments\ExpirePastAppointments;
use App\Services\CurrentSalon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentSalon $context, ExpirePastAppointments $expiration): JsonResponse
    {
        $salon = $context->get();
        $expiration->handle(salonId: $salon->id);
        $timezone = $salon->appointmentSetting?->timezone ?? 'Asia/Tehran';
        $now = CarbonImmutable::now($timezone);
        $todayStart = $now->startOfDay()->utc();
        $tomorrowStart = $now->addDay()->startOfDay()->utc();

        $todayAppointments = $salon->appointments()->visibleTo($request->user(), $salon->id)
            ->where('starts_at', '>=', $todayStart)
            ->where('starts_at', '<', $tomorrowStart);

        $statusCounts = (clone $todayAppointments)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $upcoming = $salon->appointments()->visibleTo($request->user(), $salon->id)
            ->where('starts_at', '>=', $now->utc())
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->with(['customer:id,name,mobile', 'items.provider.user:id,name'])
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn ($appointment) => [
                'id' => $appointment->id,
                'status' => $appointment->status->value,
                'starts_at' => $appointment->starts_at->timezone($timezone)->format('Y-m-d H:i'),
                'customer' => [
                    'name' => $appointment->customer->name,
                    'mobile' => $appointment->customer->mobile,
                ],
                'services' => $appointment->items->pluck('service_name')->values(),
                'employees' => $appointment->items->pluck('provider.user.name')->filter()->unique()->values(),
            ]);

        $employees = SalonUser::query()
            ->where('salon_id', $salon->id)
            ->where('role', SalonRole::Employee->value)
            ->where('is_active', true)
            ->with([
                'user:id,name',
                'availabilityDays' => fn ($query) => $query
                    ->whereDate('date', $now->toDateString())
                    ->with('periods'),
            ])
            ->get()
            ->map(function (SalonUser $membership) {
                $day = $membership->availabilityDays->first();

                return [
                    'membership_id' => $membership->id,
                    'name' => $membership->user->name,
                    'schedule_status' => ! $day ? 'not_set' : ($day->is_present ? 'present' : 'absent'),
                    'periods' => $day?->periods->map(fn ($period) => [
                        'starts_at' => substr((string) $period->starts_at, 0, 5),
                        'ends_at' => substr((string) $period->ends_at, 0, 5),
                    ])->values() ?? [],
                ];
            });

        return response()->json(['data' => [
            'date' => $now->toDateString(),
            'summary' => [
                'today_appointments' => (clone $todayAppointments)->count(),
                'today_confirmed' => (int) ($statusCounts[AppointmentStatus::Confirmed->value] ?? 0),
                'today_completed' => (int) ($statusCounts[AppointmentStatus::Completed->value] ?? 0),
                'today_cancelled' => (int) ($statusCounts[AppointmentStatus::Cancelled->value] ?? 0),
                'today_expired' => (int) ($statusCounts[AppointmentStatus::Expired->value] ?? 0),
                'active_customers' => $salon->users()->wherePivot('role', SalonRole::Customer->value)->wherePivot('is_active', true)->count(),
                'active_employees' => $employees->count(),
                'active_services' => $salon->services()->where('is_active', true)->count(),
            ],
            'upcoming_appointments' => $upcoming,
            'employees' => $employees,
        ]]);
    }
}
