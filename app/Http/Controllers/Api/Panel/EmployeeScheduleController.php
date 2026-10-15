<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Models\AppointmentItem;
use App\Models\SalonUser;
use App\Notifications\WorkScheduleChanged;
use App\Services\CurrentSalon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleController extends Controller
{
    public function employees(Request $request, CurrentSalon $context): JsonResponse
    {
        $this->ensureOwner($request, $context);
        $employees = SalonUser::query()->where('salon_id', $context->get()->id)
            ->where('role', SalonRole::Employee->value)->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with('user:id,name,image')->get()->map(fn ($member) => [
                'membership_id' => $member->id, 'name' => $member->user->name,
                'image_url' => $this->imageUrl($member->user->image),
            ]);

        return response()->json(['data' => $employees]);
    }

    public function show(Request $request, CurrentSalon $context): JsonResponse
    {
        $input = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'membership_id' => ['nullable', 'integer'],
        ]);
        $from = CarbonImmutable::createFromFormat('Y-m-d', $input['from']);
        $to = CarbonImmutable::createFromFormat('Y-m-d', $input['to']);
        abort_if($from->diffInDays($to) > 41, 422, 'بازه برنامه بیشتر از ۴۲ روز مجاز نیست.');
        $membership = $this->membership($request, $context, $input['membership_id'] ?? null);
        $availability = $membership->availabilityDays()->with('periods')
            ->whereBetween('date', [$input['from'], $input['to']])->get()->keyBy(fn ($day) => $day->date->format('Y-m-d'));
        $salonDays = $context->get()->workingDays()->with('periods')
            ->whereBetween('date', [$input['from'], $input['to']])->get()->keyBy(fn ($day) => $day->date->format('Y-m-d'));
        $days = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $salonDay = $salonDays->get($key);
            $employeeDay = $availability->get($key);
            $salonPeriods = $salonDay?->periods->map(fn ($period) => $this->period($period))->values()->all()
                ?? [['starts_at' => '09:00', 'ends_at' => '17:00']];
            $days[] = [
                'date' => $key,
                'salon_is_open' => $salonDay?->is_open ?? true,
                'salon_title' => $salonDay?->title,
                'salon_periods' => $salonDay?->is_open === false ? [] : $salonPeriods,
                'is_present' => $employeeDay?->is_present ?? false,
                'periods' => $employeeDay?->periods->map(fn ($period) => $this->period($period))->values() ?? [],
            ];
        }

        return response()->json(['data' => [
            'employee' => [
                'membership_id' => $membership->id,
                'name' => $membership->user->name,
                'image_url' => $this->imageUrl($membership->user->image),
            ],
            'planning_period' => $context->get()->appointmentSetting?->planning_period ?? 'weekly',
            'days' => $days,
        ]]);
    }

    public function update(Request $request, string $date, CurrentSalon $context): JsonResponse
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);
        $input = $request->validate([
            'membership_id' => ['nullable', 'integer'],
            'is_present' => ['required', 'boolean'],
            'periods' => ['present', 'array'],
            'periods.*.starts_at' => ['required', 'date_format:H:i'],
            'periods.*.ends_at' => ['required', 'date_format:H:i'],
        ]);
        $membership = $this->membership($request, $context, $input['membership_id'] ?? null);
        $periods = collect($input['periods'])->sortBy('starts_at')->values();
        if ($input['is_present'] && $periods->isEmpty()) {
            throw ValidationException::withMessages(['periods' => 'برای حضور حداقل یک بازه وارد کنید.']);
        }
        foreach ($periods as $index => $period) {
            if ($period['ends_at'] <= $period['starts_at']) {
                throw ValidationException::withMessages(["periods.{$index}.ends_at" => 'ساعت پایان باید بعد از شروع باشد.']);
            }
            if ($index && $period['starts_at'] < $periods[$index - 1]['ends_at']) {
                throw ValidationException::withMessages(['periods' => 'بازه‌های حضور نباید هم‌پوشانی داشته باشند.']);
            }
        }
        $salonDay = $context->get()->workingDays()->with('periods')->whereDate('date', $date)->first();
        if ($salonDay && ! $salonDay->is_open && $input['is_present']) {
            throw ValidationException::withMessages(['date' => 'سالن در این روز تعطیل است.']);
        }
        $salonPeriods = $salonDay?->periods->map(fn ($period) => $this->period($period))
            ?? collect([['starts_at' => '09:00', 'ends_at' => '17:00']]);
        foreach ($periods as $index => $period) {
            if (! $salonPeriods->contains(fn ($open) => $period['starts_at'] >= $open['starts_at'] && $period['ends_at'] <= $open['ends_at'])) {
                throw ValidationException::withMessages(["periods.{$index}" => 'بازه حضور باید داخل ساعات کاری سالن باشد.']);
            }
        }
        $this->ensureAppointmentsRemainValid($membership, $date, (bool) $input['is_present'], $periods);

        DB::transaction(function () use ($membership, $date, $input, $periods) {
            $day = $membership->availabilityDays()->updateOrCreate(['date' => $date], ['is_present' => $input['is_present']]);
            $day->periods()->delete();
            if ($input['is_present']) {
                foreach ($periods as $index => $period) {
                    $day->periods()->create([...$period, 'sort_order' => $index]);
                }
            }
        });

        if ($this->isOwner($request, $context)) {
            $membership->user->notify(new WorkScheduleChanged($context->get()->id, $context->get()->name, $date));
        }

        return response()->json(['message' => 'برنامه حضور ذخیره شد.']);
    }

    private function membership(Request $request, CurrentSalon $context, ?int $requestedId): SalonUser
    {
        $query = SalonUser::query()->where('salon_id', $context->get()->id)
            ->where('role', SalonRole::Employee->value)->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))->with('user');
        if ($this->isOwner($request, $context)) {
            abort_unless($requestedId, 422, 'انتخاب کارمند الزامی است.');

            return $query->findOrFail($requestedId);
        }
        $membership = $query->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless(in_array('appointments', $membership->permissionNames(), true), 403);
        abort_if($requestedId && $requestedId !== $membership->id, 403);

        return $membership;
    }

    private function isOwner(Request $request, CurrentSalon $context): bool
    {
        return $request->user()->isSuperAdmin() || $request->user()->salons()->whereKey($context->get()->id)
            ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->exists();
    }

    private function ensureOwner(Request $request, CurrentSalon $context): void
    {
        abort_unless($this->isOwner($request, $context), 403, 'فقط مدیر سالن به برنامه همه کارکنان دسترسی دارد.');
    }

    private function period(object $period): array
    {
        return ['starts_at' => substr($period->starts_at, 0, 5), 'ends_at' => substr($period->ends_at, 0, 5)];
    }

    private function imageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : Storage::url($image);
    }

    private function ensureAppointmentsRemainValid(SalonUser $membership, string $date, bool $isPresent, $periods): void
    {
        $timezone = $membership->user->salons()->whereKey($membership->salon_id)->first()?->appointmentSetting?->timezone ?? 'Asia/Tehran';
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} 00:00", $timezone)->utc();
        $end = $start->addDay();
        $items = AppointmentItem::query()->where('salon_user_id', $membership->id)
            ->whereHas('appointment', fn ($query) => $query->whereNotIn('status', ['cancelled', 'rejected']))
            ->where('starts_at', '>=', $start)->where('starts_at', '<', $end)->get();
        $conflicts = $items->filter(function ($item) use ($isPresent, $periods, $timezone) {
            if (! $isPresent) {
                return true;
            }
            $itemStart = $item->starts_at->timezone($timezone)->format('H:i');
            $itemEnd = $item->ends_at->timezone($timezone)->format('H:i');

            return ! $periods->contains(fn ($period) => $itemStart >= $period['starts_at'] && $itemEnd <= $period['ends_at']);
        })->pluck('appointment_id')->unique()->values();
        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages(['appointments' => 'ابتدا نوبت‌های متداخل را جابه‌جا یا لغو کنید: '.$conflicts->implode('، ')]);
        }
    }
}
