<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $employees = [
            ['mobile' => '09111111101', 'first_name' => 'مریم', 'last_name' => 'احمدی'],
            ['mobile' => '09111111102', 'first_name' => 'نگار', 'last_name' => 'کریمی'],
            ['mobile' => '09111111103', 'first_name' => 'سارا', 'last_name' => 'محمدی'],
            ['mobile' => '09111111104', 'first_name' => 'الهام', 'last_name' => 'رضایی'],
        ];

        foreach ($employees as $data) {
            $user = User::withTrashed()->firstOrNew(['mobile' => $data['mobile']]);
            $user->fill([
                'name' => $data['first_name'].' '.$data['last_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'mobile_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->deleted_at = null;
            $user->save();

            $salon->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => SalonRole::Employee->value,
                    'permissions' => json_encode([
                        'dashboard', 'service_categories', 'manage_service_categories',
                        'services', 'manage_services', 'appointments',
                    ], JSON_UNESCAPED_UNICODE),
                    'is_active' => true,
                ],
            ]);
        }
    }
}
