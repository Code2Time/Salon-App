<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalonSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // The owner middleware enforces access in the resolved salon.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->name)) {
            $this->merge(['name' => trim($this->name)]);
        }
        if (is_string($this->phone)) {
            $this->merge(['phone' => strtr($this->phone, array_combine(
                preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
                str_split('01234567890123456789'),
            ))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'aftercare_discount_validity_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'description' => ['nullable', 'string', 'max:3000'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9()\s-]{5,30}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'prohibited_if:remove_logo,1'],
            'remove_logo' => ['sometimes', 'boolean'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:500'],
            'telegram_url' => ['nullable', 'url:http,https', 'max:500'],
            'whatsapp_url' => ['nullable', 'url:http,https', 'max:500'],
            'website_url' => ['nullable', 'url:http,https', 'max:500'],
            'salon_id' => ['prohibited'],
            'slug' => ['prohibited'],
        ];
    }
}
