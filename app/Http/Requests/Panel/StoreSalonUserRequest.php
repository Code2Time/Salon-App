<?php

namespace App\Http\Requests\Panel;

use App\Enums\SalonRole;
use App\Services\Otp\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreSalonUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        try {
            $this->merge(['mobile' => IranianMobile::normalize((string) $this->input('mobile'))]);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['mobile' => $exception->getMessage()]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:70'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(SalonRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(['dashboard', 'service_categories', 'manage_service_categories', 'services', 'manage_services', 'appointments', 'products', 'reports'])],
        ];
    }
}
