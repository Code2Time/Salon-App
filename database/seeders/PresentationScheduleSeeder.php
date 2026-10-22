<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PresentationScheduleSeeder extends Seeder
{
    private const DEMO_EMPLOYEES = [
        ['mobile' => '09900001001', 'first_name' => 'نیلوفر', 'last_name' => 'احمدی', 'services' => ['کوتاهی و براشینگ', 'رنگ کامل مو', 'کراتین و احیای مو']],
        ['mobile' => '09900001002', 'first_name' => 'مریم', 'last_name' => 'رضایی', 'services' => ['مانیکور و لاک ژل', 'کاشت ناخن']],
        ['mobile' => '09900001003', 'first_name' => 'سارا', 'last_name' => 'محمدی', 'services' => ['فیشال و آبرسانی', 'میکاپ مهمانی', 'میکاپ عروس']],
    ];

    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $today = CarbonImmutable::now($salon->appointmentSetting?->timezone ?? 'Asia/Tehran')->startOfDay();
        $weekStart = $today->subDays(($today->dayOfWeek + 1) % 7);
        $weekEnd = $weekStart->addDays(27);
        $salonDays = $salon->workingDays()->with('periods')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()->keyBy(fn ($day) => $day->date->toDateString());

        $createdDays = 0;
        $createdPeriods = 0;

        DB::transaction(function () use ($salon, $weekStart, $salonDays, &$createdDays, &$createdPeriods): void {
            $employees = SalonUser::query()->where('salon_id', $salon->id)
                ->where('role', SalonRole::Employee->value)->where('is_active', true)
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->orderBy('id')->get();

            foreach (self::DEMO_EMPLOYEES as $demo) {
                $name = $demo['first_name'].' '.$demo['last_name'];
                $user = User::withTrashed()->where('mobile', $demo['mobile'])->first();
                if ($user && ($user->trashed() || $user->name !== $name)) {
                    throw new RuntimeException("شماره نمایشی {$demo['mobile']} پیش‌تر برای حساب دیگری استفاده شده است.");
                }
                if (! $user) {
                    $user = User::query()->create([
                        'mobile' => $demo['mobile'],
                        'name' => $name,
                        'first_name' => $demo['first_name'],
                        'last_name' => $demo['last_name'],
                        'mobile_verified_at' => now(),
                        'is_active' => true,
                        'is_first_login' => false,
                    ]);
                }

                $membership = SalonUser::query()->where('salon_id', $salon->id)->where('user_id', $user->id)->first();
                if (! $membership) {
                    $salon->users()->attach($user->id, [
                        'role' => SalonRole::Employee->value,
                        'permissions' => ['dashboard', 'services', 'appointments'],
                        'is_active' => true,
                    ]);
                    $membership = SalonUser::query()->where('salon_id', $salon->id)->where('user_id', $user->id)->firstOrFail();
                }
                if (! $membership->is_active || $membership->role !== SalonRole::Employee || ! $user->is_active) {
                    continue;
                }
                $serviceIds = $salon->services()->where('is_active', true)->whereIn('name', $demo['services'])->pluck('id')->all();
                $membership->services()->syncWithoutDetaching($serviceIds);
                $employees->push($membership);
            }

            foreach ($employees->unique('id')->values() as $employeeIndex => $employee) {
                for ($dayIndex = 0; $dayIndex < 28; $dayIndex++) {
                    $date = $weekStart->addDays($dayIndex)->toDateString();
                    $salonDay = $salonDays->get($date);
                    if ($salonDay?->is_open === false || $employee->availabilityDays()->whereDate('date', $date)->exists()) {
                        continue;
                    }

                    $openPeriods = $salonDay
                        ? $salonDay->periods->map(fn ($period) => ['starts_at' => $period->starts_at, 'ends_at' => $period->ends_at])->all()
                        : [['starts_at' => '09:00', 'ends_at' => '17:00']];
                    $periods = $this->splitPeriods($openPeriods, $employeeIndex, $dayIndex);
                    if ($periods === []) {
                        continue;
                    }

                    $day = $employee->availabilityDays()->create(['date' => $date, 'is_present' => true]);
                    foreach ($periods as $sortOrder => $period) {
                        $day->periods()->create([...$period, 'sort_order' => $sortOrder]);
                        $createdPeriods++;
                    }
                    $createdDays++;
                }
            }
        });

        $this->command?->info("برنامه نمایشی: {$createdDays} روز و {$createdPeriods} بازه حضور جدید ثبت شد.");
    }

    /** @param array<int, array{starts_at: string, ends_at: string}> $openPeriods
     * @return array<int, array{starts_at: string, ends_at: string}>
     */
    private function splitPeriods(array $openPeriods, int $employeeIndex, int $dayIndex): array
    {
        $result = [];
        foreach ($openPeriods as $open) {
            $openStart = $this->minutes($open['starts_at']);
            $openEnd = $this->minutes($open['ends_at']);
            $start = $openStart + 15 * (($employeeIndex + $dayIndex) % 3);
            $end = $openEnd - 15 * (($employeeIndex + 2 * $dayIndex) % 2);
            if ($end - $start < 60) {
                $start = $openStart;
                $end = $openEnd;
            }
            if ($end - $start < 45) {
                continue;
            }

            $count = $end - $start >= 330 ? 3 : ($end - $start >= 180 ? 2 : 1);
            $chunk = intdiv(intdiv($end - $start - 15 * ($count - 1), $count), 15) * 15;
            for ($index = 0; $index < $count; $index++) {
                $finish = $index === $count - 1 ? $end : $start + $chunk;
                $result[] = ['starts_at' => $this->time($start), 'ends_at' => $this->time($finish)];
                $start = $finish + 15;
            }
        }

        return $result;
    }

    private function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
