<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->discount_code)) {
            $this->merge(['discount_code' => strtoupper(trim($this->discount_code))]);
        }
    }

    public function rules(): array
    {
        return [
            'request_id' => [$this->is('api/orders/quote') ? 'nullable' : 'required', 'uuid'],
            'salon_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_code' => ['nullable', 'string', 'max:40'],
            'discount_codes' => ['prohibited'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['required', 'array:product_id,quantity,expected_unit_price'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.expected_unit_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }
}
