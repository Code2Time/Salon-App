<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $timezone = $this->salon?->appointmentSetting?->timezone ?? 'Asia/Tehran';

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'source' => $this->source,
            'starts_at' => $this->starts_at->timezone($timezone)->format('Y-m-d H:i'),
            'ends_at' => $this->ends_at->timezone($timezone)->format('Y-m-d H:i'),
            'total_price' => $this->total_price,
            'required_deposit_amount' => $this->required_deposit_amount,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'customer' => $this->whenLoaded('customer', fn () => ['id' => $this->customer->id, 'name' => $this->customer->name, 'mobile' => $this->customer->mobile]),
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id, 'service_id' => $item->service_id, 'service_name' => $item->service_name,
                'employee_membership_id' => $item->salon_user_id, 'employee_name' => $item->provider?->user?->name,
                'price' => $item->price, 'duration_minutes' => $item->duration_minutes,
                'starts_at' => $item->starts_at->timezone($timezone)->format('Y-m-d H:i'),
                'ends_at' => $item->ends_at->timezone($timezone)->format('Y-m-d H:i'),
            ])->values(),
        ];
    }
}
