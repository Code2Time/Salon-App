<?php

namespace App\Http\Requests\Panel;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalonWorkingDayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_open' => ['required', 'boolean'],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'confirm_employee_impact' => ['sometimes', 'boolean'],
            'periods' => ['present', 'array', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->boolean('is_open') && count($value) === 0) {
                    $fail('برای روز باز حداقل یک بازه کاری وارد کنید.');
                }
            }],
            'periods.*.starts_at' => ['required', 'date_format:H:i'],
            'periods.*.ends_at' => ['required', 'date_format:H:i', 'after:periods.*.starts_at'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if (! $this->boolean('is_open')) {
                return;
            }

            $periods = collect($this->input('periods', []))->sortBy('starts_at')->values();
            for ($index = 1; $index < $periods->count(); $index++) {
                if ($periods[$index]['starts_at'] < $periods[$index - 1]['ends_at']) {
                    $validator->errors()->add('periods', 'بازه‌های کاری نباید با یکدیگر هم‌پوشانی داشته باشند.');
                    break;
                }
            }
        }];
    }
}
