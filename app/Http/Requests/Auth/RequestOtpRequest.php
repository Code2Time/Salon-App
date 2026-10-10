<?php

namespace App\Http\Requests\Auth;

use App\Services\Otp\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RequestOtpRequest extends FormRequest
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
        return ['mobile' => ['required', 'regex:/^09\d{9}$/']];
    }
}
