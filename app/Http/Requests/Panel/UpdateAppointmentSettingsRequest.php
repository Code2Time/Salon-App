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
        ];
    }
}
