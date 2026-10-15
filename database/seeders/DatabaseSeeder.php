<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $legacyMobiles = [
            '09111111001',
            '09111111002',
            '09111111003',
            '09111111004',
            '09111111005',
            '09111111101',
            '09111111102',
            '09111111103',
            '09111111104',
        ];
        $legacyUserIds = User::withTrashed()->whereIn('mobile', $legacyMobiles)->pluck('id');
        DB::table('salon_user')->whereIn('user_id', $legacyUserIds)->update(['is_active' => false]);
        User::query()->whereIn('id', $legacyUserIds)->delete();

        $salon = Salon::query()->updateOrCreate(
            ['slug' => 'ara'],
            ['name' => 'سالن آرا', 'is_active' => true],
        );

        $users = [
            ['mobile' => '09333218766', 'first_name' => 'مدیر', 'last_name' => 'سالن', 'role' => SalonRole::Owner],
            ['mobile' => '09111111111', 'first_name' => 'مشتری', 'last_name' => 'آزمایشی', 'role' => SalonRole::Customer],
        ];

        foreach ($users as $data) {
            $user = User::withTrashed()->firstOrNew(['mobile' => $data['mobile']]);
            $user->fill([
                'name' => $data['first_name'].' '.$data['last_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'mobile_verified_at' => now(),
                'system_role' => $data['system_role'] ?? null,
                'is_active' => true,
            ]);
            $user->deleted_at = null;
            $user->save();

            if (isset($data['role'])) {
                $salon->users()->syncWithoutDetaching([
                    $user->id => ['role' => $data['role']->value, 'is_active' => true],
                ]);
            }
        }

        $this->call([
            EmployeeSeeder::class,
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
