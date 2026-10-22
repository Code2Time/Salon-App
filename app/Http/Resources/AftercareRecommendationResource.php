<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AftercareRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $available = $this->discountIsAvailable();
        $price = $this->product->price;

        return [
            'id' => $this->id, 'salon_id' => $this->salon_id, 'salon_name' => $this->salon->name,
            'appointment_id' => $this->appointment_id, 'care_instructions' => $this->care_instructions,
            'product' => new ProductResource($this->product),
            'discount_code' => $this->discount_code, 'discount_percent' => $this->discount_percent,
            'discount_available' => $available,
            'discounted_price' => $available ? $price - intdiv($price * $this->discount_percent, 100) : $price,
            'expires_at' => $this->expires_at?->toISOString(), 'redeemed_at' => $this->redeemed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
