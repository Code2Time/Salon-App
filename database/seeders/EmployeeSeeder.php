<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $employees = [
            ['mobile' => '09222222222', 'first_name' => 'کارمند', 'last_name' => 'آزمایشی'],
            ['mobile' => '09333333333', 'first_name' => 'کارمند دوم', 'last_name' => 'آزمایشی', 'seed_schedule' => true],
        ];

        foreach ($employees as $data) {
            $user = User::withTrashed()->firstOrNew(['mobile' => $data['mobile']]);
            $user->fill([
                'name' => $data['first_name'].' '.$data['last_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'mobile_verified_at' => now(),
                'system_role' => null,
                'is_active' => true,
            ]);
            $user->deleted_at = null;
            $user->save();

            $salon->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => SalonRole::Employee->value,
                    'permissions' => [
                        'dashboard', 'service_categories', 'manage_service_categories',
                        'services', 'manage_services', 'appointments',
                    ],
                    'is_active' => true,
                ],
            ]);

            if ($data['seed_schedule'] ?? false) {
                $membership = $salon->users()->whereKey($user->id)->firstOrFail()->pivot;
                $date = CarbonImmutable::now('Asia/Tehran')->startOfDay();
                $seededDays = 0;

                while ($seededDays < 3) {
                    $salonDay = $salon->workingDays()->whereDate('date', $date->toDateString())->first();
                    if ($salonDay?->is_open ?? true) {
                        $day = $membership->availabilityDays()->updateOrCreate(
                            ['date' => $date->toDateString()],
                            ['is_present' => true],
                        );
                        $day->periods()->delete();
                        $day->periods()->create([
                            'starts_at' => '09:00',
                            'ends_at' => '17:00',
                            'sort_order' => 0,
                        ]);
                        $seededDays++;
                    }

                    $date = $date->addDay();
                }
            }
        }
    }
}
