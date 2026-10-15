<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateAppointmentSettingsRequest;
use App\Http\Requests\Panel\UpdateSalonWorkingDayRequest;
use App\Models\AppointmentSetting;
use App\Models\EmployeeAvailabilityDay;
use App\Models\SalonWorkingDay;
use App\Notifications\WorkDayCancelled;
use App\Services\CurrentSalon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentScheduleController extends Controller
{
    public function show(Request $request, CurrentSalon $context): JsonResponse
    {
        $range = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = CarbonImmutable::createFromFormat('Y-m-d', $range['from'])->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $range['to'])->startOfDay();
        abort_if($from->diffInDays($to) > 41, 422, 'بازه نمایش تقویم نمی‌تواند بیشتر از ۴۲ روز باشد.');

        $salon = $context->get();
        $settings = $this->settings($salon->id);
        $days = $salon->workingDays()->with('periods')
            ->whereBetween('date', [$range['from'], $range['to']])
            ->orderBy('date')->get()->map(fn (SalonWorkingDay $day) => $this->dayData($day));

        return response()->json(['data' => [
            'settings' => $this->settingsData($settings),
            'days' => $days,
        ]]);
    }

    public function updateSettings(
        UpdateAppointmentSettingsRequest $request,
        CurrentSalon $context,
    ): JsonResponse {
        $settings = $this->settings($context->get()->id);
        $settings->update($request->validated());

        return response()->json(['data' => $this->settingsData($settings->fresh())]);
    }

    public function updateDay(
        UpdateSalonWorkingDayRequest $request,
        string $date,
        CurrentSalon $context,
    ): JsonResponse {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1, 404);
        $parsedDate = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        abort_if(! $parsedDate || $parsedDate->format('Y-m-d') !== $date, 404);

        $data = $request->validated();
        $salon = $context->get();
        $affectedDays = collect();
        if (! $data['is_open']) {
            $affectedDays = EmployeeAvailabilityDay::query()
                ->whereDate('date', $date)
                ->where('is_present', true)
                ->whereHas('membership', fn ($query) => $query
                    ->where('salon_id', $salon->id)
                    ->where('is_active', true)
                    ->whereHas('user', fn ($userQuery) => $userQuery->where('is_active', true)))
                ->with('membership.user:id,name')
                ->get();

            if ($affectedDays->isNotEmpty() && ! ($data['confirm_employee_impact'] ?? false)) {
                return response()->json([
                    'message' => 'برای این روز برنامه حضور کارکنان ثبت شده است. برای تعطیل‌کردن روز، تغییر را تأیید کنید.',
                    'code' => 'employee_schedule_conflict',
                    'employees' => $affectedDays->map(fn ($day) => [
                        'membership_id' => $day->membership->id,
                        'name' => $day->membership->user->name,
                    ])->values(),
                ], 409);
            }
        }

        $day = DB::transaction(function () use ($salon, $date, $data, $affectedDays) {
            $day = $salon->workingDays()->updateOrCreate(
                ['date' => $date],
                [
                    'is_open' => $data['is_open'],
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                ],
            );
            $day->periods()->delete();
            if ($data['is_open']) {
                foreach (collect($data['periods'])->sortBy('starts_at')->values() as $index => $period) {
                    $day->periods()->create([
                        'starts_at' => $period['starts_at'],
                        'ends_at' => $period['ends_at'],
                        'sort_order' => $index,
                    ]);
                }
            } else {
                foreach ($affectedDays as $employeeDay) {
                    $employeeDay->periods()->delete();
                    $employeeDay->update(['is_present' => false]);
                }
            }

            return $day;
        });

        if (! $data['is_open']) {
            foreach ($affectedDays as $employeeDay) {
                $employeeDay->membership->user->notify(new WorkDayCancelled($salon->id, $salon->name, $date));
            }
        }

        return response()->json(['data' => $this->dayData($day->load('periods'))]);
    }

    private function settings(int $salonId): AppointmentSetting
    {
        return AppointmentSetting::query()->firstOrCreate(
            ['salon_id' => $salonId],
            ['planning_period' => 'weekly', 'is_recurring' => false, 'timezone' => 'Asia/Tehran'],
        );
    }

    /** @return array<string, mixed> */
    private function settingsData(AppointmentSetting $settings): array
    {
        return [
            'planning_period' => $settings->planning_period,
            'is_recurring' => $settings->is_recurring,
            'timezone' => $settings->timezone,
            'slot_interval_minutes' => $settings->slot_interval_minutes,
            'cancellation_notice_hours' => $settings->cancellation_notice_hours,
            'deposit_enabled' => $settings->deposit_enabled,
            'deposit_type' => $settings->deposit_type,
            'deposit_value' => $settings->deposit_value,
        ];
    }

    /** @return array<string, mixed> */
    private function dayData(SalonWorkingDay $day): array
    {
        return [
            'id' => $day->id,
            'date' => $day->date->format('Y-m-d'),
            'is_open' => $day->is_open,
            'title' => $day->title,
            'description' => $day->description,
            'periods' => $day->periods->map(fn ($period) => [
                'id' => $period->id,
                'starts_at' => substr($period->starts_at, 0, 5),
                'ends_at' => substr($period->ends_at, 0, 5),
            ])->values(),
        ];
    }
}
