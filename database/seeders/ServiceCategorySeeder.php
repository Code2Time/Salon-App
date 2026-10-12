<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::query()->where('slug', 'ara')->firstOrFail();
        $categories = [
            ['name' => 'مو', 'description' => 'کوتاهی، حالت‌دهی و مراقبت تخصصی مو', 'is_featured' => true, 'sort_order' => 10],
            ['name' => 'رنگ و لایت', 'description' => 'رنگ، مش، لایت و تکنیک‌های تخصصی رنگ مو', 'is_featured' => true, 'sort_order' => 20],
            ['name' => 'ناخن', 'description' => 'خدمات زیبایی و مراقبت دست و ناخن', 'is_featured' => true, 'sort_order' => 30],
            ['name' => 'پوست و زیبایی', 'description' => 'پاک‌سازی، آبرسانی و مراقبت پوستی', 'is_featured' => false, 'sort_order' => 40],
            ['name' => 'میکاپ', 'description' => 'میکاپ روزانه، مهمانی و تخصصی', 'is_featured' => false, 'sort_order' => 50],
            ['name' => 'عروس', 'description' => 'پکیج‌های اختصاصی آماده‌سازی و میکاپ عروس', 'is_featured' => true, 'sort_order' => 60],
        ];

        foreach ($categories as $data) {
            $category = ServiceCategory::withTrashed()->firstOrNew([
                'salon_id' => $salon->id,
                'name' => $data['name'],
            ]);
            $category->fill([...$data, 'is_active' => true]);
            $category->deleted_at = null;
            $category->save();
        }
    }
}
