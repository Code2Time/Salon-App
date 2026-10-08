<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Enums\SystemRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $salon = Salon::query()->updateOrCreate(
            ['slug' => 'ara'],
            ['name' => 'سالن آرا', 'is_active' => true],
        );

        $users = [
            ['mobile' => '09111111001', 'first_name' => 'مدیر کل', 'last_name' => 'آزمایشی', 'system_role' => SystemRole::SuperAdmin],
            ['mobile' => '09111111002', 'first_name' => 'مالک', 'last_name' => 'آزمایشی', 'role' => SalonRole::Owner],
            ['mobile' => '09111111003', 'first_name' => 'مدیر', 'last_name' => 'آزمایشی', 'role' => SalonRole::Manager],
            ['mobile' => '09111111004', 'first_name' => 'کارمند', 'last_name' => 'آزمایشی', 'role' => SalonRole::Employee],
            ['mobile' => '09111111005', 'first_name' => 'مشتری', 'last_name' => 'آزمایشی'],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['mobile' => $data['mobile']],
                [
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'mobile_verified_at' => now(),
                    'system_role' => $data['system_role'] ?? null,
                    'is_active' => true,
                ],
            );

            if (isset($data['role'])) {
                $salon->users()->syncWithoutDetaching([
                    $user->id => ['role' => $data['role']->value, 'is_active' => true],
                ]);
            }
        }
    }
}
