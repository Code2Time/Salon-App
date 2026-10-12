<?php

namespace Database\Seeders;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\SalonUser;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $categories = $salon->serviceCategories()->pluck('id', 'name');
        $employeeMobiles = ['09111111101', '09111111102', '09111111103', '09111111104'];
        $providers = SalonUser::query()
            ->where('salon_id', $salon->id)
            ->where('role', SalonRole::Employee->value)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('mobile', $employeeMobiles))
            ->with('user:id,mobile')
            ->get()
            ->sortBy(fn ($membership) => array_search($membership->user->mobile, $employeeMobiles, true))
            ->pluck('id')
            ->values();

        $services = [
            ['name' => 'کوتاهی و براشینگ', 'description' => 'کوتاهی متناسب با فرم صورت همراه براشینگ', 'price' => 450000, 'duration_minutes' => 60, 'categories' => ['مو'], 'providers' => [0, 1]],
            ['name' => 'کراتین و احیای مو', 'description' => 'احیای تخصصی و کاهش وز مو با مواد حرفه‌ای', 'price' => 2800000, 'duration_minutes' => 180, 'categories' => ['مو'], 'providers' => [0]],
            ['name' => 'رنگ کامل مو', 'description' => 'رنگ کامل با مشاوره انتخاب تناژ مناسب', 'price' => 1900000, 'duration_minutes' => 150, 'categories' => ['مو', 'رنگ و لایت'], 'providers' => [0, 1]],
            ['name' => 'بالیاژ و لایت', 'description' => 'اجرای بالیاژ و لایت متناسب با پایه مو', 'price' => 3500000, 'duration_minutes' => 240, 'categories' => ['رنگ و لایت'], 'providers' => [1]],
            ['name' => 'مانیکور و لاک ژل', 'description' => 'مانیکور کامل و اجرای لاک ژل', 'price' => 650000, 'duration_minutes' => 75, 'categories' => ['ناخن'], 'providers' => [2]],
            ['name' => 'کاشت ناخن', 'description' => 'کاشت و فرم‌دهی ناخن با طراحی ساده', 'price' => 950000, 'duration_minutes' => 120, 'categories' => ['ناخن'], 'providers' => [2]],
            ['name' => 'فیشال و آبرسانی', 'description' => 'پاک‌سازی عمیق و آبرسانی متناسب با نوع پوست', 'price' => 1200000, 'duration_minutes' => 90, 'categories' => ['پوست و زیبایی'], 'providers' => [3]],
            ['name' => 'میکاپ مهمانی', 'description' => 'میکاپ حرفه‌ای و ماندگار متناسب با چهره', 'price' => 1800000, 'duration_minutes' => 90, 'categories' => ['میکاپ'], 'providers' => [3]],
            ['name' => 'میکاپ عروس', 'description' => 'میکاپ تخصصی عروس همراه با تست اولیه', 'price' => 6500000, 'duration_minutes' => 180, 'categories' => ['میکاپ', 'عروس'], 'providers' => [3]],
            ['name' => 'پکیج کامل عروس', 'description' => 'میکاپ، شینیون و آماده‌سازی کامل عروس', 'price' => 12000000, 'duration_minutes' => 300, 'categories' => ['مو', 'میکاپ', 'عروس'], 'providers' => [0, 3]],
        ];

        foreach ($services as $data) {
            $service = Service::withTrashed()->firstOrNew([
                'salon_id' => $salon->id,
                'name' => $data['name'],
            ]);
            $service->fill([
                'description' => $data['description'],
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'is_active' => true,
            ]);
            $service->deleted_at = null;
            $service->save();
            $service->categories()->sync(collect($data['categories'])->map(fn ($name) => $categories->get($name))->filter()->all());
            $service->providers()->sync(collect($data['providers'])->map(fn ($index) => $providers->get($index))->filter()->all());
        }
    }
}
