<?php

namespace App\Http\Requests\Panel;

use App\Enums\SalonRole;
use App\Services\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function rules(): array
    {
        $salonId = app(CurrentSalon::class)->get()->id;
        $serviceId = $this->route('service')?->id;

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('services')->ignore($serviceId)->where(fn ($query) => $query
                    ->where('salon_id', $salonId)->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'is_active' => ['required', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => [
                'integer', 'distinct',
                Rule::exists('service_categories', 'id')->where(fn ($query) => $query
                    ->where('salon_id', $salonId)->whereNull('deleted_at')),
            ],
            'provider_membership_ids' => ['nullable', 'array'],
            'provider_membership_ids.*' => [
                'integer', 'distinct',
                Rule::exists('salon_user', 'id')->where(fn ($query) => $query
                    ->where('salon_id', $salonId)
                    ->where('role', SalonRole::Employee->value)
                    ->where('is_active', true)),
            ],
        ];
    }
}
