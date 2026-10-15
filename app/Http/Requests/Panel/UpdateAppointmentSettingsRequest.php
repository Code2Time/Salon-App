<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'planning_period' => ['required', Rule::in(['weekly', 'monthly'])],
            'is_recurring' => ['required', 'boolean'],
            'deposit_enabled' => ['sometimes', 'boolean'],
            'deposit_type' => ['nullable', 'required_if:deposit_enabled,true', Rule::in(['fixed', 'percentage'])],
            'deposit_value' => ['nullable', 'required_if:deposit_enabled,true', 'integer', 'min:1', 'max:'.($this->input('deposit_type') === 'percentage' ? 100 : 999999999999)],
        ];
    }
}
